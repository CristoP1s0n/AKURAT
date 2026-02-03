<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:database-backup')->dailyAt('01:00');

// (Opsional) Lock System: Jika ingin menutup triwulan secara otomatis di tanggal tertentu
// Contoh: Kunci Triwulan 1 setiap tanggal 5 April
Schedule::call(function () {
    // Logic untuk mengubah status periode di tabel settings/konfigurasi
    \Log::info("Sistem secara otomatis mengunci periode Triwulan 1");
})->yearlyOn(4, 5, '00:00');
