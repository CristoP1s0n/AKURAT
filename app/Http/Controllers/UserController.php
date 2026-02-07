<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'kadis') { abort(403); }

        $pegawai = User::with('unitKerja')->orderBy('nama', 'asc')->get();
        
        // Ambil data untuk dropdown di modal
        $units = UnitKerja::orderBy('nama_unit', 'asc')->get();
        
        // Ambil calon atasan (semua kecuali staff)
        $atasans = User::whereIn('role', ['kadis', 'kabag', 'kasie'])->orderBy('nama', 'asc')->get();

        return view('pegawai.index', compact('pegawai', 'units', 'atasans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|unique:users,nip',
            'jabatan' => 'required|string',
            'golongan' => 'required|string',
            'unit_id' => 'required|exists:unit_kerja,id',
            'role' => 'required|in:kadis,kabag,kasie,staff',
            'parent_id' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'nama' => $request->nama,
                'nip' => $request->nip,
                'jabatan' => $request->jabatan,
                'golongan' => $request->golongan,
                'unit_id' => $request->unit_id,
                'role' => $request->role,
                'parent_id' => $request->parent_id,
                'is_active' => true,
                'password' => Hash::make($request->nip), // Default password adalah NIP
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'CREATE_USER',
                'subject_table' => 'users',
                'subject_id' => $user->id,
                'description' => "Menambahkan pegawai baru: {$user->nama} (NIP: {$user->nip})",
                'ip_address' => $request->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Pegawai baru berhasil didaftarkan. Password default adalah NIP.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambah pegawai: ' . $e->getMessage());
        }
    }
}
