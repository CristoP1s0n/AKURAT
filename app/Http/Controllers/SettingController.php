<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function setPeriode(Request $request)
    {
        $request->validate([
            'triwulan' => 'required|in:1,2,3,4',
        ]);

        // Simpan periode pilihan ke dalam session
        session(['periode_pilihan' => $request->triwulan]);

        return back()->with('success', 'Periode berhasil diubah ke Triwulan '.$request->triwulan);
    }

    public function resetPeriode()
    {
        // Hapus session agar kembali ke default sistem
        session()->forget('periode_pilihan');

        return back()->with('success', 'Periode telah dikembalikan ke default sistem.');
    }
}
