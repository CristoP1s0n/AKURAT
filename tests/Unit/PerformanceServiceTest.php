<?php

namespace Tests\Unit;

use App\Models\KriteriaTupoksi;
use App\Models\Penilaian;
use App\Models\Tupoksi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\PerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Test untuk PerformanceService
 *
 * Menguji tiga risiko utama perhitungan skor:
 * 1. Akurasi formula: (skorDiperoleh / (totalKriteria * 3)) * 100
 * 2. Isolasi data per-triwulan (kriteria Q1 tidak ikut dihitung di Q2)
 * 3. Akurasi threshold predikat (Sangat Baik / Baik / Cukup / Kurang)
 */
class PerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private PerformanceService $service;
    private User $staff;
    private int $tahun = 2026;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PerformanceService();

        // Wajib: UserFactory default menggunakan unit_id = 1
        UnitKerja::create([
            'id' => 1,
            'nama_unit' => 'Dinas Kesehatan Kota Manado',
            'level' => 'bidang',
        ]);

        // Seed threshold nilai dari SettingSeeder (nilai produksi)
        \DB::table('settings')->insert([
            ['key' => 'skor_sangat_baik', 'value' => '90'],
            ['key' => 'skor_baik',        'value' => '76'],
            ['key' => 'skor_cukup',       'value' => '60'],
        ]);

        // Buat user staff sebagai subjek penilaian
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'unit_id' => 1,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // KELOMPOK 1: Akurasi Formula Perhitungan Skor
    // ──────────────────────────────────────────────────────────

    /**
     * Jika semua kriteria mendapat skor maksimal (3),
     * nilai harus tepat 100.00.
     */
    public function test_skor_sempurna_menghasilkan_100(): void
    {
        $this->buatKriteriaDanPenilaian(triwulan: 1, jumlah: 3, skorPerItem: 3);

        $nilai = $this->service->hitungNilaiTriwulan($this->staff, 1, $this->tahun);

        $this->assertEquals(100.00, $nilai);
    }

    /**
     * Jika tidak ada penilaian sama sekali (supervisor belum menilai),
     * nilai harus 0 — bukan error atau null.
     */
    public function test_skor_nol_jika_tidak_ada_penilaian(): void
    {
        // Buat kriteria tapi TIDAK buat penilaian
        $tupoksi = Tupoksi::create([
            'user_id' => $this->staff->id,
            'nama_tupoksi' => 'Administrasi Umum',
            'tahun' => $this->tahun,
        ]);

        KriteriaTupoksi::create([
            'tupoksi_id' => $tupoksi->id,
            'nama_kriteria' => 'Membuat laporan bulanan',
            't1' => true, 't2' => true, 't3' => true, 't4' => true,
        ]);

        $nilai = $this->service->hitungNilaiTriwulan($this->staff, 1, $this->tahun);

        $this->assertEquals(0.00, $nilai);
    }

    /**
     * Verifikasi formula: 3 kriteria dengan skor [2, 2, 2]
     * = (6 / 9) * 100 = 66.67
     */
    public function test_skor_sebagian_dihitung_benar(): void
    {
        $this->buatKriteriaDanPenilaian(triwulan: 1, jumlah: 3, skorPerItem: 2);

        $nilai = $this->service->hitungNilaiTriwulan($this->staff, 1, $this->tahun);

        // (6 / 9) * 100 = 66.666... → round ke 2 desimal = 66.67
        $this->assertEquals(66.67, $nilai);
    }

    /**
     * Kriteria yang hanya aktif di Q1 (t1=true, t2=false) tidak boleh
     * ikut dihitung saat menghitung skor Q2.
     */
    public function test_kriteria_triwulan_lain_tidak_ikut_dihitung(): void
    {
        $tupoksi = Tupoksi::create([
            'user_id' => $this->staff->id,
            'nama_tupoksi' => 'Tupoksi Test Isolasi',
            'tahun' => $this->tahun,
        ]);

        // Kriteria ini hanya untuk Q1
        $kriteriaQ1 = KriteriaTupoksi::create([
            'tupoksi_id' => $tupoksi->id,
            'nama_kriteria' => 'Hanya Q1',
            't1' => true, 't2' => false, 't3' => false, 't4' => false,
        ]);

        // Kriteria ini untuk semua triwulan
        $kriteriaAll = KriteriaTupoksi::create([
            'tupoksi_id' => $tupoksi->id,
            'nama_kriteria' => 'Semua Triwulan',
            't1' => true, 't2' => true, 't3' => true, 't4' => true,
        ]);

        // Beri penilaian skor 3 untuk Q2 pada kriteria 'Semua Triwulan'
        Penilaian::create([
            'kriteria_id' => $kriteriaAll->id,
            'user_id' => $this->staff->id,
            'triwulan' => 2,
            'tahun' => $this->tahun,
            'penilai_id' => $this->staff->id,
            'skor' => 3,
        ]);

        // Skor Q2: hanya 1 kriteria aktif (kriteriaAll), skor 3 → (3/3)*100 = 100
        $nilaiQ2 = $this->service->hitungNilaiTriwulan($this->staff, 2, $this->tahun);
        $this->assertEquals(100.00, $nilaiQ2);
    }

    // ──────────────────────────────────────────────────────────
    // KELOMPOK 2: Akurasi Nilai Tahunan
    // ──────────────────────────────────────────────────────────

    /**
     * Nilai tahunan = rata-rata 4 triwulan.
     * Q1=100, Q2=0, Q3=0, Q4=0 → rata-rata = 25.00
     */
    public function test_nilai_tahunan_adalah_rata_rata_4_triwulan(): void
    {
        // Hanya buat kriteria + penilaian untuk Q1 (nilai = 100)
        $this->buatKriteriaDanPenilaian(triwulan: 1, jumlah: 1, skorPerItem: 3);
        // Q2, Q3, Q4 tidak ada kriteria → hitungNilaiTriwulan mengembalikan 0

        $nilaiTahunan = $this->service->hitungNilaiTahunan($this->staff, $this->tahun);

        $this->assertEquals(25.00, $nilaiTahunan);
    }

    // ──────────────────────────────────────────────────────────
    // KELOMPOK 3: Threshold Predikat Kinerja
    // ──────────────────────────────────────────────────────────

    /** Skor tepat batas bawah 'Sangat Baik' (90) harus masuk "Sangat Baik" */
    public function test_predikat_sangat_baik_pada_batas_bawah(): void
    {
        $this->assertEquals('Sangat Baik', $this->service->getPredikat(90));
    }

    /** Skor 89.99 — tepat di bawah threshold "Sangat Baik" — harus jadi "Baik" */
    public function test_predikat_baik_tepat_di_bawah_threshold_sangat_baik(): void
    {
        $this->assertEquals('Baik', $this->service->getPredikat(89.99));
    }

    /** Skor tepat batas bawah 'Baik' (76) */
    public function test_predikat_baik_pada_batas_bawah(): void
    {
        $this->assertEquals('Baik', $this->service->getPredikat(76));
    }

    /** Skor tepat batas bawah 'Cukup' (60) */
    public function test_predikat_cukup_pada_batas_bawah(): void
    {
        $this->assertEquals('Cukup', $this->service->getPredikat(60));
    }

    /** Skor di bawah 60 masuk kategori "Kurang" */
    public function test_predikat_kurang_di_bawah_cukup(): void
    {
        $this->assertEquals('Kurang / Tidak Memenuhi', $this->service->getPredikat(59.99));
    }

    // ──────────────────────────────────────────────────────────
    // Helper: Buat data kriteria + penilaian dalam satu triwulan
    // ──────────────────────────────────────────────────────────

    /**
     * Helper method untuk menyiapkan data uji dengan cepat.
     * Membuat 1 Tupoksi → $jumlah KriteriaTupoksi → Penilaian untuk triwulan tertentu.
     */
    private function buatKriteriaDanPenilaian(int $triwulan, int $jumlah, int $skorPerItem): void
    {
        $tupoksi = Tupoksi::create([
            'user_id' => $this->staff->id,
            'nama_tupoksi' => 'Pelaksanaan Program Kesehatan',
            'tahun' => $this->tahun,
        ]);

        $kolom = 't' . $triwulan;

        for ($i = 1; $i <= $jumlah; $i++) {
            $kriteria = KriteriaTupoksi::create([
                'tupoksi_id' => $tupoksi->id,
                'nama_kriteria' => "Kriteria Uji $i",
                't1' => false, 't2' => false, 't3' => false, 't4' => false,
                $kolom => true,
            ]);

            Penilaian::create([
                'kriteria_id' => $kriteria->id,
                'user_id' => $this->staff->id,
                'triwulan' => $triwulan,
                'tahun' => $this->tahun,
                'penilai_id' => $this->staff->id,
                'skor' => $skorPerItem,
            ]);
        }
    }
}
