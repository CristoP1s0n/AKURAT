<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckQuarterLock
{
    public function handle(Request $request, Closure $next)
    {
        // Deteksi triwulan dari input atau route
        $triwulan = $request->triwulan ?? $request->route('triwulan');
        if (! $triwulan) {
            $msg = 'Parameter periode tidak ditemukan sehingga keamanan periode gagal divalidasi.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 400);
            }

            return back()->with('error', $msg);
        }

        // Ambil data dengan 1 Query efisien
        $settings = \DB::table('settings')->whereIn('key', [
            'lock_t'.$triwulan,
            't'.$triwulan.'_deadline',
        ])->get()->pluck('value', 'key');

        $isManualLock = ($settings['lock_t'.$triwulan] ?? '0') == '1';
        $deadline = $settings['t'.$triwulan.'_deadline'] ?? null;
        $isPastDeadline = $deadline ? now()->gt(\Carbon\Carbon::parse($deadline)) : false;

        // Hak istimewa Kadis/Admin (Sesuai Dokumen Hal 6 & 9)
        if (auth()->user()->role !== 'kadis') {
            if ($isManualLock || $isPastDeadline) {
                $msg = "Maaf, akses untuk Triwulan $triwulan sudah ditutup atau melewati batas waktu.";

                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 403);
                }

                return back()->with('error', $msg);
            }
        }

        return $next($request);
    }
}
