<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakTime extends Model
{
    protected $fillable = ['attendance_id','start','end'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
