<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerkasKinerja extends Model
{
    protected $table = 'berkas_kinerja';

    protected $fillable = [
        'user_id', 
        'tupoksi_id',
        'triwulan', 
        'tahun', 
        'file_path', 
        'status_penilaian'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kriteria()
    {
        return $this->belongsTo(KriteriaTupoksi::class, 'kriteria_id');
    }

    // Relasi 1-to-1 ke Penilaian (Sesuai dokumen hal 6)
    public function penilaian()
    {
        return $this->hasOne(Penilaian::class, 'berkas_id');
    }

    public function penilai()
    {
        return $this->hasOneThrough(
            User::class,         // Model akhir yang ingin dicapai
            Penilaian::class,    // Model perantara
            'berkas_id',         // Foreign key di tabel Penilaian
            'id',                // Foreign key di tabel User (ID user)
            'id',                // Local key di tabel BerkasKinerja
            'penilai_id'         // Local key di tabel Penilaian
        );
    }

    public function getNamaPenilaiAttribute()
    {
        // Menggunakan Null Safe Operator (?->) Laravel untuk menghindari error jika belum dinilai
        return $this->penilai?->nama ?? 'Belum Dinilai';
    }
}
