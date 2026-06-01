<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitKerjaController extends Controller
{
    public function index()
    {
        $units = UnitKerja::all();
        $users = User::all();

        return view('unit_kerja.index', compact('units', 'users'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'kadis') {
            abort(403);
        }

        $request->validate([
            'nama_unit' => 'required|string|max:255',
            'level' => 'required|in:bidang,seksi',
            'parent_id' => 'required|exists:unit_kerja,id',
        ]);

        DB::beginTransaction();
        try {
            $unit = UnitKerja::create([
                'nama_unit' => $request->nama_unit,
                'level' => $request->level,
                'parent_id' => $request->parent_id ?: null,
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'CREATE_UNIT_KERJA',
                'subject_table' => 'unit_kerja',
                'subject_id' => $unit->id,
                'description' => "Menambahkan unit kerja baru: {$unit->nama_unit} (Level: {$unit->level})",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return back()->with('success', "Unit kerja '{$unit->nama_unit}' berhasil ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menambah unit kerja: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'kadis') {
            abort(403);
        }

        $unit = UnitKerja::findOrFail($id);

        $request->validate([
            'nama_unit' => 'required|string|max:255',
            'level' => 'required|in:bidang,bagian,seksi',
            'parent_id' => 'nullable|exists:unit_kerja,id',
        ]);

        // Cegah unit menjadi parent dari dirinya sendiri
        if ($request->parent_id && (int) $request->parent_id === $unit->id) {
            return back()->with('error', 'Unit kerja tidak dapat menjadi induk dari dirinya sendiri.');
        }

        DB::beginTransaction();
        try {
            $namaLama = $unit->nama_unit;

            $unit->update([
                'nama_unit' => $request->nama_unit,
                'level' => $request->level,
                'parent_id' => $request->parent_id ?: null,
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'UPDATE_UNIT_KERJA',
                'subject_table' => 'unit_kerja',
                'subject_id' => $unit->id,
                'description' => "Memperbarui unit kerja: '{$namaLama}' → '{$unit->nama_unit}'",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return back()->with('success', "Unit kerja berhasil diperbarui menjadi '{$unit->nama_unit}'.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui unit kerja: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        if (auth()->user()->role !== 'kadis') {
            abort(403);
        }

        $unit = UnitKerja::findOrFail($id);

        // Guard 1: Cek apakah ada pegawai yang masih terdaftar di unit ini
        $jumlahPegawai = $unit->users()->count();
        if ($jumlahPegawai > 0) {
            return back()->with('error',
                "Tidak dapat menghapus unit &ldquo;<strong>{$unit->nama_unit}</strong>&rdquo;. "
                ."Masih ada <strong>{$jumlahPegawai} pegawai</strong> yang terdaftar di unit ini. "
                .'Pindahkan semua pegawai ke unit lain terlebih dahulu.'
            );
        }

        // Guard 2: Cek apakah ada sub-unit (anak) di bawah unit ini
        $jumlahSubUnit = $unit->children()->count();
        if ($jumlahSubUnit > 0) {
            return back()->with('error',
                "Tidak dapat menghapus unit &ldquo;<strong>{$unit->nama_unit}</strong>&rdquo;. "
                ."Masih ada <strong>{$jumlahSubUnit} sub-unit</strong> di bawahnya. "
                .'Hapus atau pindahkan sub-unit tersebut terlebih dahulu.'
            );
        }

        DB::beginTransaction();
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'DELETE_UNIT_KERJA',
                'subject_table' => 'unit_kerja',
                'subject_id' => $unit->id,
                'description' => "Menghapus unit kerja: {$unit->nama_unit} (Level: {$unit->level})",
                'ip_address' => $request->ip(),
            ]);

            $unit->delete();

            DB::commit();

            return back()->with('success', "Unit kerja '{$unit->nama_unit}' berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus unit kerja: '.$e->getMessage());
        }
    }
}
