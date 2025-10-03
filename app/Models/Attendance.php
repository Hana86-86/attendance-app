<?php

namespace App\Models;

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

    /** @return BelongsTo<User,Attendance> */
    public function user(): BelongsTo
    {
        // 日本語：attendance は user に属する
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<BreakTime> */
    public function breakTimes(): HasMany
    {
        // 日本語：休憩時間は複数、開始時刻順で取得
        return $this->hasMany(BreakTime::class)->orderBy('start');
    }

    // 日本語：今開いている休憩（end が null）が存在するか？
    public function hasOpenBreak(): bool
    {
        return $this->breakTimes()->whereNull('end')->exists();
    }

    // 日本語：今日・本人のスコープ（ auth() が前提になる）
    public function scopeToday($query)
    {
        return $query
            ->where('user_id', auth()->id())
            ->where('work_date', now()->toDateString());
    }
}




