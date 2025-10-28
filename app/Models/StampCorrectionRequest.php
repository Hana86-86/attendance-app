<?php

namespace App\Models;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
    'user_id',
    'attendance_id',
    'requested_clock_in',
    'requested_clock_out',
    'requested_break', // JSON で受け取る
    'reason',
    'status',
];

protected $casts = [
    'requested_clock_in'  => 'datetime',
    'requested_clock_out' => 'datetime',
    'requested_break'     => 'array',
];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

