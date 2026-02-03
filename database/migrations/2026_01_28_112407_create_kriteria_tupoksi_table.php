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
        Schema::create('kriteria_tupoksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tupoksi_id')->constrained('tupoksi')->onDelete('cascade');
            $table->string('nama_kriteria');
            $table->text('keterangan')->nullable();
            $table->boolean('t1')->default(true);
            $table->boolean('t2')->default(true);
            $table->boolean('t3')->default(true);
            $table->boolean('t4')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kriteria_tupoksi');
    }
};
