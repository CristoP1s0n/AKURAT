<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nonaktifkan transaction wrapper untuk migration ini.
     *
     * SQLite tidak mengizinkan perubahan PRAGMA di dalam transaction aktif.
     * Laravel secara default membungkus setiap migration dalam transaction,
     * sehingga PRAGMA foreign_keys=OFF menjadi no-op jika $withinTransaction = true.
     * Referensi: https://www.sqlite.org/pragma.html#pragma_foreign_keys
     */
    public bool $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * Kompatibilitas SQLite: SQLite merekonstruksi seluruh tabel saat DROP COLUMN.
     * Jika kolom yang di-drop memiliki FK constraint, SQLite gagal validasi setelah
     * rekonstruksi. Solusi: matikan FK enforcement sementara dengan PRAGMA.
     * PRAGMA hanya bisa dijalankan di LUAR transaction (lihat $withinTransaction).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        Schema::table('berkas_kinerja', function (Blueprint $table) {
            $table->foreignId('tupoksi_id')->nullable()->constrained('tupoksi')->onDelete('cascade');
            $table->dropColumn('kriteria_id');
        });

        Schema::table('penilaian', function (Blueprint $table) {
            // dropForeign hanya didukung PostgreSQL & MySQL, bukan SQLite
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['berkas_id']);
            }
            $table->dropColumn('berkas_id');
            $table->foreignId('kriteria_id')->constrained('kriteria_tupoksi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('triwulan');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        //
    }
};
