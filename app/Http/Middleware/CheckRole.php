<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Cek apakah user sudah login dan apakah role user ada dalam daftar roles yang diizinkan
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            abort(403, 'Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}