<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    // 1. Definisikan properti
    protected $perfService;

    // 2. Inisialisasi melalui Constructor Injection
    public function __construct(PerformanceService $perfService)
    {
        $this->perfService = $perfService;
    }
    
    public function index()
    {
        $user = auth()->user();
        $tahun = DB::table('settings')->where('key', 'tahun_aktif')->value('value');
        
        if ($user->role == 'kadis') {
            // Kadis melihat semua pegawai
            $dataLaporan = \App\Models\User::where('role', '!=', 'kadis')->get();
        } elseif (in_array($user->role, ['kabag', 'kasie'])) {
            // Atasan melihat bawahannya
            $dataLaporan = \App\Models\User::where('parent_id', $user->id)->get();
        } else {
            // Staff hanya melihat dirinya sendiri
            $dataLaporan = \App\Models\User::where('id', $user->id)->get();
        }

        // Gunakan PerformanceService untuk setiap user
        foreach ($dataLaporan as $u) {
            $u->skor_tahunan = $this->perfService->hitungNilaiTahunan($u, $tahun);
            $u->predikat_tahunan = $this->perfService->getPredikat($u->skor_tahunan);
        }

        return view('laporan.index', compact('dataLaporan', 'tahun'));
    }
}
