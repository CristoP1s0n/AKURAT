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
     * Catatan kompatibilitas: SQLite tidak mendukung DROP FOREIGN KEY.
     * Pengecekan driver DB memastikan migrasi ini berjalan di PostgreSQL (prod)
     * maupun SQLite in-memory (CI testing).
     */
    public function up(): void
    {
        Schema::table('berkas_kinerja', function (Blueprint $table) {
            $table->foreignId('tupoksi_id')->nullable()->constrained('tupoksi')->onDelete('cascade');
            $table->dropColumn('kriteria_id');
        });

        Schema::table('penilaian', function (Blueprint $table) {
            // dropForeign hanya didukung oleh PostgreSQL & MySQL, bukan SQLite
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['berkas_id']);
            }
            $table->dropColumn('berkas_id');
            // Penilaian sekarang langsung ke kriteria dan user
            $table->foreignId('kriteria_id')->constrained('kriteria_tupoksi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('triwulan');
        });
    }

    public function down(): void
    {
        //
    }
};

