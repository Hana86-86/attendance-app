<?php
// app/Models/Attendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Attendance extends Model
{

    protected $fillable = [
        'user_id',      // ユーザーID
        'work_date',    // 勤務日（DATE）
        'clock_in',     // 出勤（DATETIME/TIME）
        'clock_out',    // 退勤（DATETIME/TIME）
        'status',       // 勤怠状態: working / closed など
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes(): HasMany
    {
        return $this->hasMany(BreakTime::class)->orderBy('start');
    }

    public function hasOpenBreak(): bool
    {
        return $this->breakTimes()->whereNull('end')->exists();
    }

    public function scopeForToday($q, $tz = null)
    {
        $tz    = $tz ?: config('app.timezone');
        $date = Carbon::now($tz)->startOfDay()->toDateString(); // 'YYYY-MM-DD'
        return $q->whereDate('work_date', $date);
    }
}