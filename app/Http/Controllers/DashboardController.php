<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BerkasKinerja;
use App\Models\KriteriaTupoksi;
use App\Services\PerformanceService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $perfService;

    public function __construct(PerformanceService $perfService)
    {
        $this->perfService = $perfService;
    }

    public function index()
    {
        $triwulan = DB::table('settings')->where('key', 'triwulan_aktif')->value('value') ?? 1;
        $tahun = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        // 1. Statistik Dasar
        $totalPegawai = User::where('role', '!=', 'kadis')->count();
        $berkasBelumDinilai = BerkasKinerja::where('status_penilaian', 'belum')
                                ->where('triwulan', $triwulan)->count();

        // 2. Hitung Progress Unggah Berkas (%)
        // Total kriteria yang harus dipenuhi semua pegawai di triwulan ini
        $totalKriteriaHarusAda = KriteriaTupoksi::where('t'.$triwulan, true)->count() * $totalPegawai;
        $totalBerkasSudahUpload = BerkasKinerja::where('triwulan', $triwulan)->where('tahun', $tahun)->count();
        
        $progressUpload = $totalKriteriaHarusAda > 0 
            ? round(($totalBerkasSudahUpload / $totalKriteriaHarusAda) * 100) 
            : 0;

        // 3. Rata-rata Skor Dinas (Real-time dari Service)
        $semuaUser = User::where('role', '!=', 'kadis')->get();
        $totalSkorDinas = 0;
        foreach ($semuaUser as $u) {
            $totalSkorDinas += $this->perfService->hitungNilaiTriwulan($u, $triwulan, $tahun);
        }
        $rataRataDinas = $totalPegawai > 0 ? round($totalSkorDinas / $totalPegawai) : 0;

        return view('dashboard', compact(
            'totalPegawai', 
            'berkasBelumDinilai', 
            'progressUpload', 
            'rataRataDinas',
            'triwulan'
        ));
    }
}