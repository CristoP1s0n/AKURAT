<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->role !== 'kadis') { abort(403); }

        // 1. Inisialisasi Query
        $query = User::with('unitKerja');

        // 2. Logika Filter Pencarian Nama/NIP
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nama', 'ilike', '%' . $request->search . '%') // ilike untuk PostgreSQL agar case-insensitive
                ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        // 3. Filter Unit Kerja
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        // 4. Filter Golongan
        if ($request->filled('golongan')) {
            $query->where('golongan', $request->golongan);
        }

        // 5. Filter Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // 6. Filter Status
        if ($request->filled('status')) {
            $status = $request->status == 'aktif' ? true : false;
            $query->where('is_active', $status);
        }

        $pegawai = $query->orderBy('nama', 'asc')->get();
        
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

    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'kadis') { abort(403); }

        $user = User::findOrFail($id);

        // Validasi input
        $request->validate([
            'nama' => 'required|string|max:255',
            // NIP harus unik kecuali untuk user yang sedang diedit ini sendiri
            'nip' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'jabatan' => 'required|string',
            'golongan' => 'required|string',
            'unit_id' => 'required|exists:unit_kerja,id',
            'role' => 'required|in:kadis,kabag,kasie,staff',
            'parent_id' => 'nullable|exists:users,id',
            'is_active' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $user->update([
                'nama' => $request->nama,
                'nip' => $request->nip,
                'jabatan' => $request->jabatan,
                'golongan' => $request->golongan,
                'unit_id' => $request->unit_id,
                'role' => $request->role,
                'parent_id' => $request->parent_id,
                'is_active' => $request->is_active,
            ]);

            // Catat ke Log Aktivitas
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'UPDATE_USER',
                'subject_table' => 'users',
                'subject_id' => $user->id,
                'description' => "Memperbarui data pegawai: {$user->nama} (NIP: {$user->nip})",
                'ip_address' => $request->ip()
            ]);

            DB::commit();
            return back()->with('success', 'Data pegawai berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }
}
