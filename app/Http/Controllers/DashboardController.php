<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\BerkasKinerja;
use App\Models\KriteriaTupoksi;
use App\Models\Tupoksi;
use App\Services\PerformanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $perfService;

    public function __construct(PerformanceService $perfService)
    {
        $this->perfService = $perfService;
    }

    public function index()
    {
        $triwulan = session('periode_pilihan', ceil(date('n') / 3));
        $tahun = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        // 1. Ambil Semua Unit & Pegawai
        $allUnits = UnitKerja::all();
        $semuaPegawai = User::where('role', '!=', 'kadis')->with('unitKerja')->get();
        
        // 2. Identifikasi Unit "Level 1" (Sekretariat & Bidang-bidang)
        // Yaitu unit yang parent_id-nya menunjuk ke Root (Dinas Kesehatan)
        $rootUnit = $allUnits->whereNull('parent_id')->first();
        $topLevelUnits = $allUnits->where('parent_id', $rootUnit->id ?? 0);

        // 3. Statistik Dasar
        $totalPegawai = $semuaPegawai->count();
        $berkasBelumDinilai = BerkasKinerja::where('status_penilaian', 'belum')
                                ->where('triwulan', $triwulan)->where('tahun', $tahun)->count();

        // 4. Kalkulasi Kinerja & Pemetaan
        $dataKinerja = [];
        $skorTotalDinas = 0;
        $predikats = ['Sangat Baik' => 0, 'Baik' => 0, 'Cukup' => 0, 'Kurang / Tidak Memenuhi' => 0];

        foreach ($semuaPegawai as $p) {
            $skor = $this->perfService->hitungNilaiTriwulan($p, $triwulan, $tahun);
            $predikat = $this->perfService->getPredikat($skor);
            
            // Cari "Bidang Induk" dari pegawai ini
            $bidangId = null;
            $currentUnit = $allUnits->where('id', $p->unit_id)->first();

            if ($currentUnit) {
                // Jika unit dia adalah anak langsung Dinas, maka itu Bidangnya
                if ($currentUnit->parent_id == ($rootUnit->id ?? 0)) {
                    $bidangId = $currentUnit->id;
                } else {
                    // Jika dia di Seksi, ambil parent dari Seksi tersebut (yaitu Bidang)
                    $bidangId = $currentUnit->parent_id;
                }
            }

            $dataKinerja[] = [
                'nama' => $p->nama,
                'nip' => $p->nip,
                'skor' => $skor,
                'bidang_id' => $bidangId
            ];

            $skorTotalDinas += $skor;
            $predikats[$predikat]++;
        }

        $rataRataDinas = $totalPegawai > 0 ? round($skorTotalDinas / $totalPegawai, 2) : 0;

        // 5. Data untuk Grafik Bar (Bidang)
        $bidangAverages = [];
        foreach ($topLevelUnits as $unit) {
            $skorPegawai = collect($dataKinerja)->where('bidang_id', $unit->id)->pluck('skor');
            $avg = $skorPegawai->count() > 0 ? $skorPegawai->avg() : 0;

            $bidangAverages[] = [
                'label' => str_replace(['Bidang ', 'Sub Bagian '], '', $unit->nama_unit),
                'value' => round($avg, 2)
            ];
        }

        // 6. Progress Unggah (Berdasarkan jumlah baris Tupoksi yang ada)
        $totalTupoksiInstance = Tupoksi::where('tahun', $tahun)->count();
        $totalBerkasUploaded = BerkasKinerja::where('triwulan', $triwulan)
                                ->where('tahun', $tahun)
                                ->where('file_path', '!=', '-')
                                ->count();
        $progressUpload = $totalTupoksiInstance > 0 ? round(($totalBerkasUploaded / $totalTupoksiInstance) * 100) : 0;

        // 7. Ranking
        $sorted = collect($dataKinerja)->sortByDesc('skor');
        $top5 = $sorted->take(5);
        $bottom5 = $sorted->reverse()->take(5);

        return view('dashboard', compact(
            'totalPegawai', 'progressUpload', 'rataRataDinas', 'berkasBelumDinilai',
            'bidangAverages', 'predikats', 'top5', 'bottom5', 'triwulan', 'tahun'
        ));
    }
}