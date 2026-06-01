<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PerformanceService;
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
        $triwulanAktif = session('periode_pilihan', ceil(date('n') / 3));

        // Logika Otoritas Akses
        if ($user->role == 'kadis') {
            $dataLaporan = User::where('role', '!=', 'kadis')->with('unitKerja')->get();
        } else {
            // Kabag, Kasie, dan Staff selalu melihat diri mereka sendiri.
            // Tambahkan juga bawahan langsung jika mereka memilikinya.
            $dataLaporan = User::where('id', $user->id)
                ->orWhere('parent_id', $user->id)
                ->with('unitKerja')
                ->orderBy('role', 'asc') // Urutkan supaya atasan selalu muncul pertama
                ->get();
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
