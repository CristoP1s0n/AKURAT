<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PerformanceService;
use Illuminate\Support\Facades\View;
use App\Models\BerkasKinerja;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends  ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PerformanceService::class, function ($app) {
            return new PerformanceService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Masukkan logika View Composer di sini
        View::composer('layouts.app', function ($view) {
            // Cek apakah user sudah login sebelum menjalankan query
            if (auth()->check()) {
                $user = auth()->user();
                
                // Ambil Triwulan dari Session Pilihan, jika tidak ada pakai default database
                $triwulan = session('periode_pilihan', ceil(date('n') / 3));
                $tahun = DB::table('settings')->where('key', 'tahun_aktif')->value('value') ?? date('Y');

                // 1. Inisialisasi Query (Ambil berkas yang belum dinilai)
                $query = BerkasKinerja::where('status_penilaian', 'belum')
                                      ->where('triwulan', $triwulan)
                                      ->where('tahun', $tahun)
                                      ->with('user');

                // 2. Filter Otoritas (Kadin melihat semua, Atasan hanya melihat bawan)
                if ($user->role !== 'kadis') {
                    $query->whereHas('user', function($q) use ($user) {
                        $q->where('parent_id', $user->id);
                    });
                }

                // 3. Ambil data untuk dikirim ke Header
                $totalNotif = $query->count(); // Calculate count before taking a mutated limit subset
                $notifikasiBerkas = $query->latest()->take(5)->get();

                // 4. Bagikan variabel ke semua view yang menggunakan layouts.app
                $view->with(compact('notifikasiBerkas', 'totalNotif'));
            }
        });
    }
}
