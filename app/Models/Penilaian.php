<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $fillable = ['berkas_id', 'penilai_id', 'skor', 'catatan_atasan'];

    public function berkas()
    {
        return $this->belongsTo(BerkasKinerja::class, 'berkas_id');
    }

    public function penilai()
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}
