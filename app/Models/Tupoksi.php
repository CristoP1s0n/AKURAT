<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tupoksi extends Model
{
    protected $table = 'tupoksi';

    protected $fillable = ['user_id', 'nama_tupoksi', 'tahun'];

    // Relasi ke User (Pemilik Tupoksi)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Kriteria (Rincian Penilaian)
    public function kriteria()
    {
        return $this->hasMany(KriteriaTupoksi::class, 'tupoksi_id');
    }
}
