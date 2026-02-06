<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('berkas_kinerja', function (Blueprint $table) {
            $table->foreignId('tupoksi_id')->nullable()->constrained('tupoksi')->onDelete('cascade');
            $table->dropColumn('kriteria_id'); // Hapus relasi ke kriteria
        });

        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropForeign(['berkas_id']);
            $table->dropColumn('berkas_id');
            // Penilaian sekarang langsung ke kriteria dan user
            $table->foreignId('kriteria_id')->constrained('kriteria_tupoksi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('triwulan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
