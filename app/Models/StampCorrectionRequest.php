<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
        'user_id', 'attendance_id', 'status', 'payload',
    ];
    protected $casts = [
        'payload' => 'array',
    ];
}
