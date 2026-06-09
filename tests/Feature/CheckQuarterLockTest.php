<?php

namespace Tests\Feature;

use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature Test untuk middleware CheckQuarterLock
 *
 * Memverifikasi 3 skenario penguncian periode:
 * 1. Akses normal saat periode terbuka
 * 2. Pemblokiran saat manual lock aktif (lock_t{n}=1)
 * 3. Pemblokiran saat deadline sudah lewat
 * 4. Hak istimewa Kadis yang dapat bypass semua lock
 * 5. Penolakan request tanpa parameter triwulan
 * 6. Format respons JSON untuk API client
 *
 * Rute yang diuji: POST /kinerja/upload (middleware 'lock' = CheckQuarterLock)
 * Referensi route: routes/web.php baris 43-45
 */
class CheckQuarterLockTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $kadis;

    protected function setUp(): void
    {
        parent::setUp();

        // Wajib: UserFactory default menggunakan unit_id = 1
        UnitKerja::create([
            'id' => 1,
            'nama_unit' => 'Dinas Kesehatan Kota Manado',
            'level' => 'bidang',
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'unit_id' => 1,
        ]);

        $this->kadis = User::factory()->create([
            'role' => 'kadis',
            'unit_id' => 1,
        ]);

        // Daftarkan route uji yang memakai middleware 'lock' secara inline.
        // Ini menghindari kebutuhan mock KinerjaController yang kompleks.
        Route::middleware(['web', 'auth', 'active', 'lock'])
            ->post('/test-lock/{triwulan}', function () {
                return response('OK', 200);
            })->name('test.lock');
    }

    // ──────────────────────────────────────────────────────────
    // KELOMPOK 1: Akses Normal (Periode Terbuka)
    // ──────────────────────────────────────────────────────────

    /**
     * Staff HARUS bisa mengakses route saat periode belum dikunci
     * dan deadline masih di masa depan.
     */
    public function test_staff_dapat_akses_periode_terbuka(): void
    {
        $this->seedSettings(lockT1: '0', deadlineT1: date('Y').'-12-31');

        $response = $this->actingAs($this->staff)
            ->post('/test-lock/1');

        $response->assertStatus(200);
    }

    // ──────────────────────────────────────────────────────────
    // KELOMPOK 2: Pemblokiran Staff
    // ──────────────────────────────────────────────────────────

    /**
     * Staff HARUS diblokir saat admin mengaktifkan manual lock
     * (lock_t1 = '1'), terlepas dari deadline.
     */
    public function test_staff_diblokir_saat_manual_lock_aktif(): void
    {
        $this->seedSettings(lockT1: '1', deadlineT1: date('Y').'-12-31');

        $response = $this->actingAs($this->staff)
            ->post('/test-lock/1');

        // CheckQuarterLock redirect kembali dengan error jika bukan JSON
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Staff HARUS diblokir otomatis saat deadline sudah terlewat,
     * walaupun manual lock tidak diaktifkan.
     */
    public function test_staff_diblokir_saat_past_deadline(): void
    {
        // Deadline kemarin
        $kemarin = now()->subDay()->format('Y-m-d');
        $this->seedSettings(lockT1: '0', deadlineT1: $kemarin);

        $response = $this->actingAs($this->staff)
            ->post('/test-lock/1');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ──────────────────────────────────────────────────────────
    // KELOMPOK 3: Hak Istimewa Kadis
    // ──────────────────────────────────────────────────────────

    /**
     * Kadis (role kadis) HARUS TETAP BISA mengakses meskipun
     * manual lock aktif DAN deadline sudah lewat.
     * (Referensi: Dokumen AKURAT Hal 6 & 9)
     */
    public function test_kadis_dapat_akses_meski_locked_dan_past_deadline(): void
    {
        $kemarin = now()->subDay()->format('Y-m-d');
        $this->seedSettings(lockT1: '1', deadlineT1: $kemarin);

        $response = $this->actingAs($this->kadis)
            ->post('/test-lock/1');

        $response->assertStatus(200);
    }

    // ──────────────────────────────────────────────────────────
    // KELOMPOK 4: Validasi Parameter & Format Respons
    // ──────────────────────────────────────────────────────────

    /**
     * Request ke route yang TIDAK memiliki parameter triwulan
     * harus ditolak dengan status 400 (bukan 500 crash).
     */
    public function test_request_tanpa_parameter_triwulan_ditolak_400(): void
    {
        // Daftarkan route TANPA parameter {triwulan} untuk simulasi ini
        Route::middleware(['web', 'auth', 'active', 'lock'])
            ->post('/test-lock-no-param', function () {
                return response('OK', 200);
            });

        $response = $this->actingAs($this->staff)
            ->postJson('/test-lock-no-param');

        // Middleware mengembalikan 400 jika tidak ada parameter triwulan
        $response->assertStatus(400);
    }

    /**
     * API client (request dengan header Accept: application/json)
     * HARUS mendapatkan respons JSON 403, BUKAN redirect HTML.
     */
    public function test_api_client_mendapat_json_403_saat_locked(): void
    {
        $this->seedSettings(lockT1: '1', deadlineT1: date('Y').'-12-31');

        $response = $this->actingAs($this->staff)
            ->postJson('/test-lock/1');

        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $response->assertJson(['message' => 'Maaf, akses untuk Triwulan 1 sudah ditutup atau melewati batas waktu.']);
    }

    // ──────────────────────────────────────────────────────────
    // Helper: Seed data settings untuk satu triwulan
    // ──────────────────────────────────────────────────────────

    /**
     * Menyiapkan baris settings yang dibutuhkan CheckQuarterLock
     * untuk triwulan 1. Cukup untuk semua test case di atas.
     */
    private function seedSettings(string $lockT1, string $deadlineT1): void
    {
        DB::table('settings')->insert([
            ['key' => 'lock_t1', 'value' => $lockT1],
            ['key' => 't1_deadline', 'value' => $deadlineT1],
        ]);
    }
}
