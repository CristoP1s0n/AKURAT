<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KriteriaTupoksi extends Model
{
    protected $table = 'kriteria_tupoksi';

    protected $fillable = ['tupoksi_id', 'nama_kriteria', 'keterangan', 't1', 't2', 't3', 't4'];

    // Relasi ke tabel induk (Tupoksi)
    public function tupoksi()
    {
        return $this->belongsTo(Tupoksi::class, 'tupoksi_id');
    }

    // Relasi ke berkas yang diupload pegawai
    /*public function berkasKinerja()
    {
        return $this->hasMany(BerkasKinerja::class, 'kriteria_id');
    }*/

    // Query Scope untuk memfilter triwulan (seperti langkah sebelumnya)
    public function scopeAktifTriwulan($query, $triwulan)
    {
        return $query->where('t'.$triwulan, true);
    }

    public function penilaian()
    {
        // Kriteria punya banyak penilaian (T1, T2, T3, T4)
        return $this->hasMany(Penilaian::class, 'kriteria_id');
    }
}
