<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nip', 'nama', 'email', 'password', 'role', 
        'parent_id', 'jabatan', 'golongan', 'unit_id', 'is_active'
    ];

    // SANGAT PENTING: Agar password tidak muncul saat data dipanggil
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // SANGAT PENTING: Memberi tahu Laravel bahwa password adalah data rahasia
    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // --- RELASI E-KINERJA ---

    public function subordinates() {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function superior() {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function unitKerja() {
        return $this->belongsTo(UnitKerja::class, 'unit_id');
    }

    public function tupoksis() {
        return $this->hasMany(Tupoksi::class);
    }

    // Tambahkan ini di dalam class User di file User.php
    public function berkasKinerja() {
        return $this->hasMany(BerkasKinerja::class);
    }
}
