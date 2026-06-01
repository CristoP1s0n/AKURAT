<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id', 'action', 'subject_table', 'subject_id',
        'description', 'properties', 'ip_address',
    ];

    // Mengonversi kolom properties (JSON) menjadi array secara otomatis
    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
