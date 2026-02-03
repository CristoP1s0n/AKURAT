<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    protected $table = 'unit_kerja'; // Beritahu Laravel nama tabelnya
    protected $fillable = ['nama_unit', 'level', 'parent_id'];

    // Relasi ke Sub-Unit (Anak)
    public function children() {
        return $this->hasMany(UnitKerja::class, 'parent_id');
    }

    // Relasi ke Unit Induk (Bapak)
    public function parent() {
        return $this->belongsTo(UnitKerja::class, 'parent_id');
    }

    // Relasi ke Pegawai di unit ini
    public function users() {
        return $this->hasMany(User::class, 'unit_id');
    }
}
