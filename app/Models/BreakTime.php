<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakTime extends Model
{
    // 日本語：一括代入を許可（id は入れない）
    protected $fillable = [
        'attendance_id',
        'start',
        'end',
    ];

    // 日本語：開始/終了は時刻として扱いたいのでキャスト
    protected $casts = [
        'start' => 'datetime',
        'end'   => 'datetime',
    ];
    
    /** @return BelongsTo<Attendance,BreakTime> */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
