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
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->unique()->constrained('berkas_kinerja')->onDelete('cascade');
            $table->foreignId('penilai_id')->constrained('users'); // Kadis/Kabag/Kasie
            $table->enum('skor', ['0', '1', '2', '3']); // 0:Tdk ada, 1:Cukup, 2:Baik, 3:Sgt Baik
            $table->text('catatan_atasan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
