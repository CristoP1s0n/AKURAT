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

        // 1. Inisialisasi Query berdasarkan Otoritas (Dokumen Hal 6)
        if ($user->role === 'kadis') {
            // Kadin melihat semua kecuali dirinya sendiri
            $pegawaiQuery = User::where('id', '!=', $user->id);
        } else {
            // Atasan hanya melihat bawahan langsung (parent_id)
            $pegawaiQuery = User::where('parent_id', $user->id);
        }

        // 2. Tambahkan Filter Search Global (Menghidupkan Fitur di Header)
        if ($request->filled('search')) {
            $pegawaiQuery->where(function($q) use ($request) {
                // Menggunakan ilike untuk PostgreSQL (Case Insensitive)
                $q->where('nama', 'ilike', '%' . $request->search . '%')
                ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        // 3. Eksekusi dengan Eager Loading
        $triwulanAktif = session('periode_pilihan', ceil(date('n') / 3));
        $tahunAktif = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        $pegawai = $pegawaiQuery->with([
            'unitKerja',
            'tupoksis' => function($q) use ($tahunAktif) {
                $q->where('tahun', $tahunAktif);
            },
            'tupoksis.kriteria' => function($q) use ($triwulanAktif) {
                $q->where('t'.$triwulanAktif, true);
            },
            'tupoksis.kriteria.penilaian' => function($q) use ($triwulanAktif, $tahunAktif) {
                $q->where('triwulan', $triwulanAktif)
                  ->where('tahun', $tahunAktif);
            }
        ])->orderBy('nama', 'asc')->get();

        return view('penilaian.index', compact('pegawai', 'triwulanAktif', 'tahunAktif'));
    }

    /**
     * SCREEN: Detail Pegawai (Tempat Kadin tambah kriteria & Atasan menilai)
     */
    public function detailPegawai($id)
    {
        $pegawai = User::with([
            'tupoksis.berkasKinerja', 
            'tupoksis.kriteria.penilaian'
        ])->findOrFail($id);
        // security check
        if (auth()->user()->role !== 'kadis' && $pegawai->parent_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke data pegawai ini.');
        }

        $triwulanAktif = session('periode_pilihan', ceil(date('n') / 3));

        $tahunAktif = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        $pegawai->load(['tupoksis' => function($q) use ($tahunAktif) {
        $q->where('tahun', $tahunAktif);
        }, 'tupoksis.berkasKinerja' => function($q) use ($id, $triwulanAktif) {
        $q->where('user_id', $id)->where('triwulan', $triwulanAktif);
        }, 'tupoksis.kriteria']);

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
        // 1. Validasi Input
        $request->validate([
            'kriteria_id' => 'required|exists:kriteria_tupoksi,id',
            'pegawai_id'  => 'required|exists:users,id',
            'skor'        => 'required|in:0,1,2,3',
        ]);

        // 2. Ambil Global Settings (Konsisten & Fleksibel)
        $triwulanAktif = session('periode_pilihan', ceil(date('n') / 3));
        $tahunAktif    = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        DB::beginTransaction();
        try {
            /**
             * LOGIKA: Penilaian kini tidak lagi mencari berkas_id.
             * Atasan menilai KRITERIA tertentu milik PEGAWAI tertentu pada PERIODE tertentu.
             * Ini memungkinkan penilaian dilakukan kapan saja meskipun file belum diunggah.
             */
            $penilaian = Penilaian::updateOrCreate(
                [
                    'kriteria_id' => $request->kriteria_id,
                    'user_id'     => $request->pegawai_id,
                    'triwulan'    => $triwulanAktif,
                    'tahun'       => $tahunAktif, // Tambahkan tahun agar data antar tahun tidak bertabrakan
                ],
                [
                    'penilai_id'     => auth()->id(),
                    'skor'           => $request->skor,
                    'catatan_atasan' => $request->catatan_atasan
                ]
            );

            // 2. LOGIKA NOTIFIKASI: Update status berkas di level Tupoksi
            // Cari tahu kriteria ini milik tupoksi mana
            $kriteria = KriteriaTupoksi::find($request->kriteria_id);
            
            // Cari berkas yang diupload staff untuk tupoksi ini pada periode aktif
            $berkas = BerkasKinerja::where('user_id', $request->pegawai_id)
                        ->where('tupoksi_id', $kriteria->tupoksi_id)
                        ->where('triwulan', $triwulanAktif)
                        ->where('tahun', $tahunAktif)
                        ->first();

            // Jika ada berkasnya, ubah status menjadi 'sudah' agar hilang dari notifikasi bell
            if ($berkas) {
                $berkas->update(['status_penilaian' => 'sudah']);
            }

            // 3. Catat Log Aktivitas (Sesuai Kriteria Dokumen Hal 4)
            ActivityLog::create([
                'user_id'       => auth()->id(),
                'action'        => 'GIVE_SCORE',
                'subject_table' => 'penilaian',
                'subject_id'    => $penilaian->id,
                'description'   => "Memberikan skor {$request->skor} pada Kriteria ID: {$request->kriteria_id} (Pegawai ID: {$request->pegawai_id})",
                'ip_address'    => $request->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Penilaian berhasil disimpan.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error simpanPenilaian: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan penilaian. Silakan hubungi admin.');
        }
    }

    // ACTION: Staff Mengunggah Berkas Bukti
    public function storeUpload(Request $request)
    {
        // Middleware CheckQuarterLock akan memproses ini dulu
        
        $request->validate([
            'tupoksi_id' => 'required|exists:tupoksi,id',
            'triwulan'   => 'required|in:1,2,3,4', // Pastikan triwulan valid
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
                'tupoksi_id' => $request->tupoksi_id,
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
                'description' => "Staff mengunggah berkas bukti untuk Tupoksi ID: {$request->tupoksi_id}",
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
        $berkas = BerkasKinerja::findOrFail($id);

        // 1. Keamanan: Pastikan yang menghapus adalah pemiliknya
        if ($berkas->user_id !== auth()->id()) {
            abort(403, 'Anda hanya bisa menghapus berkas milik sendiri.');
        }

        // 2. Aturan Bisnis: Cek apakah ada penilaian terkait tupoksi ini untuk user dan triwulan yang sama
        // Karena penilaian sekarang terhubung ke kriteria, bukan ke berkas
        $tupoksi = $berkas->tupoksi;
        if ($tupoksi) {
            $adaPenilaian = \App\Models\Penilaian::whereHas('kriteria', function($q) use ($tupoksi) {
                $q->where('tupoksi_id', $tupoksi->id);
            })
            ->where('user_id', $berkas->user_id)
            ->where('triwulan', $berkas->triwulan)
            ->where('tahun', $berkas->tahun)
            ->exists();

            if ($adaPenilaian) {
                return back()->with('error', 'Tupoksi ini sudah dinilai, Anda tidak dapat menghapus berkas.');
            }
        }
        
        // 3. Cek Locking System
        $isLocked = DB::table('settings')->where('key', 'lock_t'.$berkas->triwulan)->where('value', '1')->exists();
        if ($isLocked) {
            return back()->with('error', 'Periode triwulan ini sudah dikunci, Anda tidak bisa menghapus berkas.');
        }

        DB::beginTransaction();
        try {
            $path = $berkas->file_path;
            $berkasId = $berkas->id;
            
            // 4. Hapus File Fisik dari Storage terlebih dahulu
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // 5. Hapus record dari database
            $berkas->delete();

            // 6. Tambahkan Log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'DELETE_BERKAS',
                'subject_table' => 'berkas_kinerjas',
                'subject_id' => $berkasId,
                'description' => "Staff menghapus berkas ID: $berkasId",
                'ip_address' => request()->ip()
            ]);

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

        $kriteria = KriteriaTupoksi::findOrFail($id);

        DB::beginTransaction();
        try {
            $namaKriteria = $kriteria->nama_kriteria;

            // 1. Hapus semua penilaian yang terikat ke kriteria ini (Clean up)
            Penilaian::where('kriteria_id', $id)->delete();

            // 2. Hapus Kriteria
            $kriteria->delete();

            // 3. Simpan Log dengan deskripsi bahaya
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'HARD_DELETE_KRITERIA',
                'subject_table' => 'kriteria_tupoksi',
                'subject_id' => $id,
                'description' => "Kadis menghapus PERMANEN kriteria: '$namaKriteria' beserta seluruh riwayat nilai di dalamnya.",
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Kriteria dan seluruh data nilai terkait berhasil dihapus permanen.');
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

    // 1. Tampilan Unggah Staff
    public function indexUpload()
    {
        $user = auth()->user();
        $triwulanAktif = session('periode_pilihan', ceil(date('n') / 3));
        $tahunAktif = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        // Mengambil Tupoksi beserta Kriteria dan Berkas yang terikat pada Tupoksi tersebut
        $tupoksis = Tupoksi::with(['kriteria' => function($q) use ($triwulanAktif) {
            $q->where('t'.$triwulanAktif, true);
        }, 'berkasKinerja' => function($q) use ($user, $triwulanAktif) {
            // Relasi berkas sekarang ke tupoksi_id (hasil migrasi terbaru)
            $q->where('user_id', $user->id)->where('triwulan', $triwulanAktif);
        }])
        ->where('user_id', $user->id)
        ->where('tahun', $tahunAktif)
        ->get();

        return view('kinerja.index', compact('tupoksis', 'triwulanAktif', 'tahunAktif'));
    }

    public function hapusTupoksi($id)
    {
        // Hanya Kadis yang boleh menghapus kontainer utama tugas
        if (auth()->user()->role !== 'kadis') {
            abort(403);
        }

        $tupoksi = Tupoksi::with('kriteria')->findOrFail($id);

        DB::beginTransaction();
        try {
            $namaTupoksi = $tupoksi->nama_tupoksi;

            // 1. Ambil semua ID kriteria di bawah tupoksi ini
            $kriteriaIds = $tupoksi->kriteria->pluck('id');

            // 2. Hapus semua penilaian yang terhubung dengan kriteria-kriteria tersebut
            Penilaian::whereIn('kriteria_id', $kriteriaIds)->delete();

            // 3. Hapus semua berkas yang terhubung dengan Tupoksi ini
            // (Opsional: Hapus file fisik di storage jika perlu)
            $berkas = BerkasKinerja::where('tupoksi_id', $id)->get();
            foreach ($berkas as $b) {
                if (Storage::disk('public')->exists($b->file_path)) {
                    Storage::disk('public')->delete($b->file_path);
                }
                $b->delete();
            }

            // 4. Hapus semua kriteria
            $tupoksi->kriteria()->delete();

            // 5. Hapus Tupoksi utama
            $tupoksi->delete();

            // 6. Simpan Log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'HARD_DELETE_TUPOKSI',
                'subject_table' => 'tupoksi',
                'subject_id' => $id,
                'description' => "Kadis menghapus PERMANEN Butir Tupoksi: '$namaTupoksi' beserta SELURUH kriteria, berkas, dan nilai di dalamnya.",
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Butir Tupoksi dan seluruh data di dalamnya berhasil dihapus permanen.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error hapusTupoksi: " . $e->getMessage());
            return back()->with('error', 'Gagal menghapus Tupoksi.');
        }
    }

    
}