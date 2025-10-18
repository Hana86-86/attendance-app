<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'status',
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