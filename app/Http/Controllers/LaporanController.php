<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    protected $perfService;

    public function __construct(PerformanceService $perfService)
    {
        $this->perfService = $perfService;
    }
    
    public function index()
    {
        $user = auth()->user();
        $tahun = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

        // AMBIL PERIODE DARI SESSION (Agar sinkron dengan Header)
        $triwulanAktif = session('periode_pilihan', DB::table('settings')->where('key', 'triwulan_aktif')->value('value') ?? 1);
        
        // Logika Otoritas Akses
        if ($user->role == 'kadis') {
            $dataLaporan = User::where('role', '!=', 'kadis')->with('unitKerja')->get();
        } elseif (in_array($user->role, ['kabag', 'kasie'])) {
            $dataLaporan = User::where('parent_id', $user->id)->with('unitKerja')->get();
        } else {
            $dataLaporan = User::where('id', $user->id)->with('unitKerja')->get();
        }

        // Kalkulasi skor tahunan untuk setiap pegawai
        foreach ($dataLaporan as $u) {
            // Service ini akan menghitung rata-rata T1, T2, T3, T4
            $u->skor_tahunan = $this->perfService->hitungNilaiTahunan($u, $tahun);
            $u->predikat_tahunan = $this->perfService->getPredikat($u->skor_tahunan);
        }

        // Kirim triwulanAktif ke view agar Header tidak error dan bisa digunakan untuk styling
        return view('laporan.index', compact('dataLaporan', 'tahun', 'triwulanAktif'));
    }
}