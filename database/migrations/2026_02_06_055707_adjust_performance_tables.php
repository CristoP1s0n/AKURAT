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
     * Menggunakan constructor (bukan property declaration) karena PHP 8.4
     * melarang redeclaration property bertipe yang sudah ada di parent class.
     * $withinTransaction sudah dideklarasikan sebagai `public bool` di
     * Illuminate\Database\Migrations\Migration — kita hanya mengubah nilainya.
     *
     * Alasan dibutuhkan: SQLite tidak mengizinkan PRAGMA foreign_keys=OFF
     * di dalam active transaction. Laravel membungkus migration dalam
     * transaction secara default. Dengan $withinTransaction = false, PRAGMA
     * dapat berjalan sebelum Schema::table() dipanggil.
     *
     * Ref: https://www.sqlite.org/pragma.html#pragma_foreign_keys
     */
    public function __construct()
    {
        $this->withinTransaction = false;
    }

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
