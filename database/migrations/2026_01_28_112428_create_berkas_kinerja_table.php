<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('berkas_kinerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('kriteria_id')->constrained('kriteria_tupoksi');
            $table->tinyInteger('triwulan'); // 1, 2, 3, 4
            $table->year('tahun');
            $table->string('file_path');
            $table->text('keterangan_pegawai')->nullable();
            $table->enum('status_penilaian', ['belum', 'sudah'])->default('belum');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas_kinerja');
    }
};
