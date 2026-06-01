<?php

namespace App\Services;

use App\Models\KriteriaTupoksi;
use App\Models\Penilaian;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PerformanceService
{
    protected $settingsCache = [];

    protected function getSetting($key, $default = null)
    {
        if (empty($this->settingsCache)) {
            $this->settingsCache = DB::table('settings')->pluck('value', 'key')->toArray();
        }

        return $this->settingsCache[$key] ?? $default;
    }

    public function hitungNilaiTriwulan(User $user, $triwulan, $tahun)
    {
        // 1. Ambil semua kriteria yang aktif untuk triwulan tersebut
        $kriteria = KriteriaTupoksi::whereHas('tupoksi', function ($q) use ($user, $tahun) {
            $q->where('user_id', $user->id)->where('tahun', $tahun);
        })->where('t'.$triwulan, true)->get();

        $totalKriteria = $kriteria->count();
        if ($totalKriteria === 0) {
            return 0;
        }

        $skorDiperoleh = 0;

        // Fix N+1 queries by fetching all criteria assessments in one query (TC-37)
        $penilaians = Penilaian::whereIn('kriteria_id', $kriteria->pluck('id'))
            ->where('user_id', $user->id)
            ->where('triwulan', $triwulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('kriteria_id');

        foreach ($kriteria as $item) {
            if (isset($penilaians[$item->id])) {
                $skorDiperoleh += (int) $penilaians[$item->id]->skor;
            }
        }

        // Rumus: (Total Skor / (Total Kriteria * 3)) * 100
        $nilai = ($skorDiperoleh / ($totalKriteria * 3)) * 100;

        return round($nilai, 2);
    }

    public function hitungNilaiTahunan(User $user, $tahun)
    {
        $t1 = $this->hitungNilaiTriwulan($user, 1, $tahun);
        $t2 = $this->hitungNilaiTriwulan($user, 2, $tahun);
        $t3 = $this->hitungNilaiTriwulan($user, 3, $tahun);
        $t4 = $this->hitungNilaiTriwulan($user, 4, $tahun);

        $rataRata = ($t1 + $t2 + $t3 + $t4) / 4;

        return round($rataRata, 2);
    }

    public function getPredikat($skor)
    {
        // Ambil ambang batas dari cache config agar tidak query berulang (TC-37)
        $sangatBaik = $this->getSetting('skor_sangat_baik', 81);
        $baik = $this->getSetting('skor_baik', 70);
        $cukup = $this->getSetting('skor_cukup', 0);

        if ($skor >= $sangatBaik) {
            return 'Sangat Baik';
        }
        if ($skor >= $baik) {
            return 'Baik';
        }
        if ($skor >= $cukup) {
            return 'Cukup';
        }

        return 'Kurang / Tidak Memenuhi';
    }
}
