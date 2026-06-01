<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KinerjaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UnitKerjaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// 1. Halaman Publik
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Rute Terproteksi (Harus Login & Akun Aktif)
// Saya tambahkan middleware 'active' agar akun non-aktif langsung tertendang
Route::middleware(['auth', 'active', 'verified'])->group(function () {

    // --- DASHBOARD ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/unit-kerja', [UnitKerjaController::class, 'index'])->name('unit-kerja.index');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

    // --- PROFIL (Breeze) ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // --- FITUR KINERJA (STAFF / SEMUA PEGAWAI) ---
    Route::prefix('kinerja')->name('kinerja.')->group(function () {

        // Rute Lihat & Download (TIDAK TERKUNCI)
        Route::get('/berkas', [KinerjaController::class, 'indexUpload'])->name('index');
        Route::get('/berkas/download/{id}', [KinerjaController::class, 'downloadBerkas'])->name('berkas.download');

        // Rute Modifikasi Data (WAJIB TERKUNCI oleh Middleware 'lock')
        Route::middleware(['lock'])->group(function () {
            Route::post('/upload', [KinerjaController::class, 'storeUpload'])->name('upload.store');
        });
        // hapusBerkas memiliki pengecekan lock internal, jadi dilepas dari middleware 'lock' agar pengecekan 403 ownership tidak tertimpa error 400 parameter triwulan
        Route::delete('/berkas/{id}', [KinerjaController::class, 'hapusBerkas'])->name('berkas.destroy');
    });

    // --- FITUR PENILAIAN (ATASAN: KADIS, KABAG, KASIE) ---
    Route::middleware(['role:kadis,kabag,kasie'])->group(function () {
        Route::get('/penilaian', [KinerjaController::class, 'indexPenilaian'])->name('penilaian.index');
        Route::get('/penilaian/pegawai/{id}', [KinerjaController::class, 'detailPegawai'])->name('penilaian.detail');
        Route::post('/penilaian/simpan', [KinerjaController::class, 'simpanPenilaian'])->name('penilaian.simpan');
    });

    // --- FITUR KHUSUS KEPALA DINAS (SUPER ADMIN) ---
    Route::middleware(['role:kadis'])->group(function () {

        // Pengaturan Periode Aktif
        Route::controller(SettingController::class)->group(function () {
            Route::post('/set-periode', 'setPeriode')->name('periode.set');
            Route::get('/reset-periode', 'resetPeriode')->name('periode.reset');
        });
        // --- DATA PEGAWAI
        Route::get('/pegawai', [UserController::class, 'index'])->name('pegawai.index');
        Route::post('/pegawai/store', [UserController::class, 'store'])->name('pegawai.store');
        Route::put('/pegawai/update/{id}', [UserController::class, 'update'])->name('pegawai.update');
        Route::delete('/pegawai/hapus/{id}', [UserController::class, 'destroy'])->name('pegawai.destroy');

        // --- UNIT KERJA (CRUD oleh Kadis)
        Route::post('/unit-kerja/store', [UnitKerjaController::class, 'store'])->name('unit-kerja.store');
        Route::put('/unit-kerja/update/{id}', [UnitKerjaController::class, 'update'])->name('unit-kerja.update');
        Route::delete('/unit-kerja/hapus/{id}', [UnitKerjaController::class, 'destroy'])->name('unit-kerja.destroy');

        // Rute Manajemen Tupoksi
        Route::post('/tupoksi/store', [KinerjaController::class, 'storeTupoksi'])->name('tupoksi.store');
        Route::delete('/tupoksi/hapus/{id}', [KinerjaController::class, 'hapusTupoksi'])->name('tupoksi.destroy');

        Route::prefix('kriteria')->name('kriteria.')->group(function () {
            Route::post('/store', [KinerjaController::class, 'storeKriteria'])->name('store');
            Route::put('/update/{id}', [KinerjaController::class, 'updateKriteria'])->name('update');
            Route::delete('/hapus/{id}', [KinerjaController::class, 'hapusKriteria'])->name('destroy');
        });

        // Contoh rute untuk Unit Kerja & Pegawai nanti
        // Route::resource('unit-kerja', UnitKerjaController::class);
        // Route::resource('pegawai', UserController::class);
    });
});

require __DIR__.'/auth.php';
