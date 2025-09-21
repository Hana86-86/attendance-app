<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'status',
    ];

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }
    public function scopeToday($query)
    {
        return $query->where('user_id',auth()->id())->where('work_date',now()->toDateString());
    }
}

class BreakTime extends Model {
    protected $fillable = [
        'attendance_id',
        'start',
        'end',
        ];
        public function attendance() {
            return $this->belongsTo(Attendance::class);
        }
}

