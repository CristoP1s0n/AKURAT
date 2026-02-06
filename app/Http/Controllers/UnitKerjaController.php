<?php

namespace App\Http\Controllers;

use App\Models\UnitKerja;
use Illuminate\Http\Request;

class UnitKerjaController extends Controller
{
    public function index()
    {
        $units = \App\Models\UnitKerja::all();
    // Ambil staff juga untuk ditampilkan di bawah seksi
        $users = \App\Models\User::with('unitKerja')->where('role', 'staff')->get();
    
        return view('unit_kerja.index', compact('units', 'users'));
    }
}
