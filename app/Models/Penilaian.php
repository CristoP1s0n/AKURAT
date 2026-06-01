<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $fillable = [
        'kriteria_id',
        'user_id',
        'triwulan',
        'tahun',
        'penilai_id',
        'skor',
        'catatan_atasan',
    ];

    public function kriteria()
    {
        return $this->belongsTo(KriteriaTupoksi::class, 'kriteria_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penilai()
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}
