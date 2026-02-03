<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Konfigurasi Periode Aktif
            ['key' => 'triwulan_aktif', 'value' => '1'],
            ['key' => 'tahun_aktif', 'value' => date('Y')],

            // Pengaturan Tanggal Batas Akhir (Deadline) Otomatis
            ['key' => 't1_deadline', 'value' => date('Y') . '-03-31'],
            ['key' => 't2_deadline', 'value' => date('Y') . '-06-30'],
            ['key' => 't3_deadline', 'value' => date('Y') . '-09-30'],
            ['key' => 't4_deadline', 'value' => date('Y') . '-12-31'],

            // Pengunci Manual (Override) - Jika 1, maka tetap terkunci walau belum deadline
            ['key' => 'lock_t1', 'value' => '0'],
            ['key' => 'lock_t2', 'value' => '0'],
            ['key' => 'lock_t3', 'value' => '0'],
            ['key' => 'lock_t4', 'value' => '0'],

            // Pengaturan Ambang Batas Nilai (Grading)
            ['key' => 'skor_sangat_baik', 'value' => '90'],
            ['key' => 'skor_baik', 'value' => '76'],
            ['key' => 'skor_cukup', 'value' => '60'],

            // Konfigurasi Sistem
            ['key' => 'max_file_size', 'value' => '5120'], // dalam KB (5MB)
            ['key' => 'allowed_extensions', 'value' => 'pdf,jpg,png,jpeg'],
            ['key' => 'app_name', 'value' => 'AKURAT - Aplikasi Kinerja Terukur'],
        ];

        foreach ($settings as $setting) {
            \DB::table('settings')->updateOrInsert(['key' => $setting['key']], $setting);
        }
    }
}
