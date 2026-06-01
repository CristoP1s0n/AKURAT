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
        Schema::table('penilaian', function (Blueprint $table) {
            // Tambahkan kolom tahun. Gunakan nullable() jika sudah ada data,
            // lalu nanti kita isi secara manual atau lewat seeder.
            if (! Schema::hasColumn('penilaian', 'tahun')) {
                $table->year('tahun')->nullable()->after('triwulan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });
    }
};
