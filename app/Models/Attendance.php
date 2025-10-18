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

    // 休憩合計（分）
    public function getBreakMinutesAttribute(): int
    {
        return $this->breakTimes->reduce(function ($sum, $bt) {
            if (!$bt->start || !$bt->end) return $sum;
            $s = Carbon::parse($bt->start);
            $e = Carbon::parse($bt->end);
            return $sum + $s->diffInMinutes($e, false);
        }, 0);
    }

    // 勤務合計（分）= 退勤-出勤 - 休憩
    public function getWorkMinutesAttribute(): ?int
    {
        if (!$this->clock_in || !$this->clock_out) return null;
        $in  = Carbon::parse($this->clock_in);
        $out = Carbon::parse($this->clock_out);
        return max(0, $in->diffInMinutes($out, false) - $this->break_minutes);
    }

    // 表示用 "H:MM"
    protected static function toHm(?int $min): string
    {
        if ($min === null) return '—';
        $h = intdiv($min, 60);
        $m = $min % 60;
        return sprintf('%d:%02d', $h, $m);
    }
    public function getBreakHmAttribute(): string { return self::toHm($this->break_minutes); }
    public function getWorkHmAttribute(): string  { return self::toHm($this->work_minutes); }
}
