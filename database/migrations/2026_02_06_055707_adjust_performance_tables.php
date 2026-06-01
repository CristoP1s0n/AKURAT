<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catatan kompatibilitas SQLite:
     * SQLite merekonstruksi seluruh tabel saat DROP COLUMN. Jika kolom yang
     * di-drop masih memiliki FK constraint, SQLite akan gagal validasi setelah
     * rekonstruksi. Solusi: matikan FK enforcement sementara selama migrasi.
     */
    public function up(): void
    {
        // Matikan FK enforcement sementara untuk SQLite (CI environment)
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

        // Aktifkan kembali FK enforcement
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        //
    }
};
