<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tupoksi;
use App\Models\KriteriaTupoksi;
use App\Models\BerkasKinerja;
use App\Models\Penilaian;
use App\Models\ActivityLog; // Tambahkan import model log
use Illuminate\Http\Request;
use App\Services\PerformanceService;
use Illuminate\Support\Facades\DB;      // Penting untuk DB::table
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KinerjaController extends Controller
{
    protected $perfService;

    public function __construct(PerformanceService $perfService)
    {
        $this->perfService = $perfService;
    }

    /**
     * SCREEN: Dashboard Penilaian (Untuk Kadis & Atasan)
     */
    public function indexPenilaian(Request $request)
    {
        $user = auth()->user();

        // Logika Otoritas (Hal 6)
        if ($user->role === 'kadis') {
            // Kadin bisa melihat semua kecuali dirinya sendiri
            $pegawai = User::where('id', '!=', $user->id)->with('unitKerja')->get();
        } else {
            // Atasan hanya melihat bawahan langsung (parent_id)
            $pegawai = User::where('parent_id', $user->id)->with('unitKerja')->get();
        }

        return view('penilaian.index', compact('pegawai'));
    }

    /**
     * SCREEN: Detail Pegawai (Tempat Kadin tambah kriteria & Atasan menilai)
     */
    public function detailPegawai($id)
    {
        $pegawai = User::with(['tupoksis.kriteria.berkasKinerja.penilaian'])->findOrFail($id);
        // security check
        if (auth()->user()->role !== 'kadis' && $pegawai->parent_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke data pegawai ini.');
        }

        $triwulanAktif = \DB::table('settings')->where('key', 'triwulan_aktif')->value('value') ?? 1;

        $tahunAktif = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        //Performance service (real time skor)
        $skorAngka = $this->perfService->hitungNilaiTriwulan($pegawai, $triwulanAktif, $tahunAktif);

        $predikat = $this->perfService->getPredikat($skorAngka);

        return view('penilaian.detail', compact('pegawai', 'triwulanAktif', 'skorAngka', 'predikat'));
    }

    /**
     * ACTION: Kadis Menambah Kriteria Baru
     */
    public function storeKriteria(Request $request)
    {


        // Hanya Kadis yang boleh mengakses method ini
        if (auth()->user()->role !== 'kadis') {
            abort(403, 'Hanya Kepala Dinas yang dapat mengelola kriteria penilaian.');
        }

        $request->validate([
            'tupoksi_id' => 'required|exists:tupoksi,id',
            'nama_kriteria' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $kriteria = KriteriaTupoksi::create([
                'tupoksi_id' => $request->tupoksi_id,
                'nama_kriteria' => $request->nama_kriteria,
                'keterangan' => $request->keterangan,
                't1' => $request->has('t1'),
                't2' => $request->has('t2'),
                't3' => $request->has('t3'),
                't4' => $request->has('t4'),
            ]);

            // 3. Simpan Log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'CREATE_KRITERIA',
                'subject_table' => 'kriteria_tupoksis',
                'subject_id' => $kriteria->id,
                'description' => "Kadis menambahkan kriteria baru: {$request->nama_kriteria}",
                'ip_address' => $request->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Kriteria penilaian berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error storeKriteria: " . $e->getMessage());
            return back()->with('error', 'Gagal menambah kriteria. Terjadi kesalahan sistem.');
        }
    }

    /**
     * ACTION: Atasan Memberikan Nilai (Skor 0-3)
     */
    public function simpanPenilaian(Request $request)
    {
        $request->validate([
            'kriteria_id' => 'required|exists:kriteria_tupoksi,id',
            'pegawai_id' => 'required|exists:users,id',
            'skor' => 'required|in:0,1,2,3',
        ]);

        $triwulanAktif = DB::table('settings')->where('key', 'triwulan_aktif')->value('value');
        $tahunAktif = DB::table('settings')->where('key', 'tahun_aktif')->value('value');

        DB::beginTransaction();
        try {

            // 1. Cek apakah ada berkas atau tidak
            $berkas = BerkasKinerja::where('kriteria_id', $request->kriteria_id)
                    ->where('user_id', $request->pegawai_id)
                    ->where('triwulan', $triwulanAktif)
                    ->first();

            // 2. Kalau belum ada, set placeholder
            if (!$berkas) {
                $berkas = BerkasKinerja::create([
                    'user_id' => $request->pegawai_id,
                    'kriteria_id' => $request->kriteria_id,
                    'triwulan' => $triwulanAktif,
                    'tahun' => $tahunAktif,
                    'file_path' => '-', // Placeholder karena tidak ada file
                    'status_penilaian' => 'belum'
                ]);
            }

            // 3. Simpan penilaian
            Penilaian::updateOrCreate(
                ['berkas_id' => $berkas->id],
                [
                    'penilai_id' => auth()->id(),
                    'skor' => $request->skor,
                    'catatan_atasan' => $request->catatan_atasan
                ]
            );

            // 4. Update status berkas jadi sudah dinilai
            $berkas->update(['status_penilaian' => 'sudah']);

            // 5. Simpan Log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'UPDATE_NILAI',
                'subject_table' => 'penilaian', 
                'subject_id' => $berkas->id,
                'description' => "Atasan memberikan skor {$request->skor} pada berkas ID: {$request->kriteria_id}",
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return back()->with('success', 'Penilaian berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("Error simpanPenilaian: " . $e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat menyimpan penilaian. Silakan coba lagi.');
        }
    }

    // ACTION: Staff Mengunggah Berkas Bukti
    public function storeUpload(Request $request)
    {
        // Middleware CheckQuarterLock akan memproses ini dulu
        
        $request->validate([
            'kriteria_id' => 'required|exists:kriteria_tupoksi,id',
            'triwulan' => 'required|in:1,2,3,4',
            'file_bukti' => 'required|mimes:pdf,jpg,png|max:5120',
        ]);
        
        DB::beginTransaction();
        try {
            $tahun = date('Y');
            $folder = "berkas-kinerja/{$tahun}/T{$request->triwulan}";

            $file = $request->file('file_bukti');
            $filename = time() . '_' . auth()->user()->nip . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folder, $filename, 'public');
            $berkas = BerkasKinerja::create([
                'user_id' => auth()->id(),
                'kriteria_id' => $request->kriteria_id,
                'triwulan' => $request->triwulan,
                'tahun' => $tahun,
                'file_path' => $path,
                'status_penilaian' => 'belum'
            ]);

            // --- TAMBAHKAN LOG ---
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'UPLOAD_BERKAS',
                'subject_table' => 'berkas_kinerjas',
                'subject_id' => $berkas->id,
                'description' => "Staff mengunggah berkas bukti untuk kriteria ID: {$request->kriteria_id}",
                'ip_address' => $request->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Berkas berhasil diunggah.');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($path)) Storage::disk('public')->delete($path);
            \Log::error("Error storeUpload: " . $e->getMessage());
            return back()->with('error', 'Gagal mengunggah berkas.');
        }
    }

    public function hapusBerkas($id)
    {
        $berkas = BerkasKinerja::with('penilaian')->findOrFail($id);

        // 1. Keamanan: Pastikan yang menghapus adalah pemiliknya
        if ($berkas->user_id !== auth()->id()) {
            abort(403, 'Anda hanya bisa menghapus berkas milik sendiri.');
        }

        // 2. Aturan Bisnis: Jika sudah dinilai, tidak boleh dihapus
        if ($berkas->penilaian) {
            return back()->with('error', 'Berkas sudah dinilai dan tidak dapat dihapus.');
        }
        
        // 3. Cek Locking System (Opsional tapi disarankan)
        $isLocked = DB::table('settings')->where('key', 'lock_t'.$berkas->triwulan)->where('value', '1')->exists();
        if ($isLocked) {
            return back()->with('error', 'Periode triwulan ini sudah dikunci, Anda tidak bisa menghapus berkas.');
        }

        DB::beginTransaction();
        try {
            $path = $berkas->file_path;
            $berkasId = $berkas->id;
            $berkas->delete();

            // --- TAMBAHKAN LOG ---
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'DELETE_BERKAS',
                'subject_table' => 'berkas_kinerjas',
                'subject_id' => $berkasId,
                'description' => "Staff menghapus berkas ID: $berkasId",
                'ip_address' => request()->ip()
            ]);

        // 4. Hapus File Fisik dari Storage
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        DB::commit();
        return back()->with('success', 'Berkas berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error hapusBerkas: " . $e->getMessage());
            return back()->with('error', 'Gagal menghapus berkas.');
        }
    }


    public function hapusKriteria($id)
    {
        if (auth()->user()->role !== 'kadis') {
            abort(403);
        }

        $kriteria = KriteriaTupoksi::with('berkasKinerja')->findOrFail($id);

        // Aturan: Jangan hapus jika sudah ada staff yang upload berkas di kriteria ini
        if ($kriteria->berkasKinerja->count() > 0) {
            return back()->with('error', 'Kriteria tidak bisa dihapus karena sudah ada pegawai yang mengunggah berkas di poin ini.');
        }

        DB::beginTransaction();
        try {
            $namaKriteria = $kriteria->nama_kriteria;
            $kriteriaId = $kriteria->id;
            $kriteria->delete();

            // --- TAMBAHKAN LOG ---
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'DELETE_KRITERIA',
                'subject_table' => 'kriteria_tupoksis',
                'subject_id' => $kriteriaId,
                'description' => "Kadis menghapus kriteria: $namaKriteria",
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Kriteria berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error hapusKriteria: " . $e->getMessage());
            return back()->with('error', 'Gagal menghapus kriteria.');
        }
    }

    public function downloadBerkas($id)
    {
        try {
            $berkas = BerkasKinerja::findOrFail($id);
            $pegawai = User::findOrFail($berkas->user_id);

            // Keamanan: Hanya Kadin atau Atasan langsung yang bisa download
            if (auth()->user()->role !== 'kadis' && $pegawai->parent_id !== auth()->id() && $berkas->user_id !== auth()->id()) {
                abort(403);
            }

            if (!Storage::disk('public')->exists($berkas->file_path)) {
                return back()->with('error', 'File tidak ditemukan di server.');
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'DOWNLOAD_BERKAS',
                'subject_table' => 'berkas_kinerjas',
                'subject_id' => $id,
                'description' => "Mendownload berkas ID: $id",
                'ip_address' => request()->ip()
            ]);

            return Storage::disk('public')->download($berkas->file_path);
        } catch (\Exception $e) {
            \Log::error("Error downloadBerkas: " . $e->getMessage());
            return back()->with('error', 'Gagal mendownload berkas.');
        }
    }

    /**
     * ACTION: Kadis Mengubah Kriteria yang Sudah Ada
     */
    public function updateKriteria(Request $request, $id)
    {
        // 1. Keamanan: Hanya Kadis
        if (auth()->user()->role !== 'kadis') {
            abort(403, 'Hanya Kepala Dinas yang dapat mengubah kriteria.');
        }

        // 2. Validasi
        $request->validate([
            'nama_kriteria' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $kriteria = KriteriaTupoksi::findOrFail($id);
            
            // Simpan data lama untuk log (opsional tapi bagus untuk audit)
            $oldName = $kriteria->nama_kriteria;

            // 3. Update Data
            $kriteria->update([
                'nama_kriteria' => $request->nama_kriteria,
                'keterangan' => $request->keterangan,
                't1' => $request->has('t1'),
                't2' => $request->has('t2'),
                't3' => $request->has('t3'),
                't4' => $request->has('t4'),
            ]);

            // 4. Simpan Log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'UPDATE_KRITERIA',
                'subject_table' => 'kriteria_tupoksi',
                'subject_id' => $kriteria->id,
                'description' => "Kadis mengubah kriteria dari '$oldName' menjadi '{$request->nama_kriteria}'",
                'ip_address' => $request->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Kriteria berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updateKriteria: " . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui kriteria.');
        }
    }

    // app/Http/Controllers/KinerjaController.php

    public function storeTupoksi(Request $request)
    {
        // Pastikan hanya Kadin yang bisa menambah butir Tupoksi
        if (auth()->user()->role !== 'kadis') {
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'nama_tupoksi' => 'required|string',
            'tahun' => 'required|numeric',
        ]);

        Tupoksi::create($request->all());

        return back()->with('success', 'Butir Tupoksi berhasil ditambahkan.');
    }

    public function indexUpload()
    {
        $user = auth()->user();
        // Ambil setting dari database
        $triwulanAktif = \DB::table('settings')->where('key', 'triwulan_aktif')->value('value') ?? 1;
        $tahunAktif = \DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        // Ambil data Tupoksi milik user yang login beserta kriterianya
        // dan cek apakah sudah ada berkas yang diunggah untuk triwulan aktif
        $tupoksis = \App\Models\Tupoksi::with(['kriteria' => function($query) use ($triwulanAktif) {
            $query->where('t' . $triwulanAktif, true);
        }, 'kriteria.berkasKinerja' => function($query) use ($user, $triwulanAktif) {
            $query->where('user_id', $user->id)->where('triwulan', $triwulanAktif);
        }, 'kriteria.berkasKinerja.penilaian'])
        ->where('user_id', $user->id)
        ->where('tahun', $tahunAktif)
        ->get();

        return view('kinerja.index', compact('tupoksis', 'triwulanAktif', 'tahunAktif'));
    }
}