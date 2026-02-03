namespace App\Services;

use App\Models\User;
use App\Models\KriteriaTupoksi;

class PerformanceService
{
    public function hitungNilaiTriwulan(User $user, $triwulan, $tahun)
    {
        // Ambil kriteria yang aktif untuk triwulan tersebut (flag t1, t2, dst)
        $kriteria = KriteriaTupoksi::whereHas('tupoksi', function($q) use ($user, $tahun) {
            $q->where('user_id', $user->id)->where('tahun', $tahun);
        })->where('t'.$triwulan, true)->get();

        $totalKriteria = $kriteria->count();
        if ($totalKriteria === 0) return 0;

        $skorDiperoleh = 0;
        foreach ($kriteria as $item) {
            // Ambil berkas kinerja milik user untuk kriteria ini
            $berkas = $user->berkasKinerja()
                ->where('kriteria_id', $item->id)
                ->where('triwulan', $triwulan)
                ->where('tahun', $tahun)
                ->first();

            // Jika sudah dinilai, tambahkan skornya
            if ($berkas && $berkas->penilaian) {
                $skorDiperoleh += (int) $berkas->penilaian->skor;
            }
        }

        // Rumus Hal 7: (Total Skor / (Total Kriteria * 3)) * 100
        $nilai = ($skorDiperoleh / ($totalKriteria * 3)) * 100;
        return round($nilai, 2);
    }

    public function getPredikat($skor)
    {
        $sangatBaik = \DB::table('settings')->where('key', 'skor_sangat_baik')->value('value');
        $baik = \DB::table('settings')->where('key', 'skor_baik')->value('value');
        $cukup = \DB::table('settings')->where('key', 'skor_cukup')->value('value');

        if ($skor >= $sangatBaik) return "Sangat Baik";
        if ($skor >= $baik) return "Baik";
        if ($skor >= $cukup) return "Cukup";
        return "Kurang / Tidak Memenuhi";
    }

    public function hitungNilaiTahunan(User $user, $tahun)
    {
        $totalNilai = 0;

        // Looping untuk mengambil nilai dari Triwulan 1 sampai 4
        for ($t = 1; $t <= 4; $t++) {
            $totalNilai += $this->hitungNilaiTriwulan($user, $t, $tahun);
        }

        // Rumus Hal 7: Nilai_Tahunan = (T1+T2+T3+T4) / 4
        $rataRata = $totalNilai / 4;

        return round($rataRata, 2);
    }
}