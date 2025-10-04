<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_id',
        'requested_clock_in',
        'requested_clock_out',
        'requested_break_start',
        'requested_break_end',
        'reason',
        'status',        // 'pending' | 'approved' | 'rejected'
        'reviewed_by',
        'reviewed_at',
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

