<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jika user sudah login...
        if (auth()->check()) {
            // ...tapi status is_active-nya false (0)
            if (!auth()->user()->is_active) {
                auth()->logout();
                
                // Hapus session agar benar-benar bersih
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan. Hubungi Admin.');
            }
        }

        return $next($request);
    }
}
