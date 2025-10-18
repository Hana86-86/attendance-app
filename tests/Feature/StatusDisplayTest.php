<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-10-17 10:00:00');
    }

    /** 勤務外：出勤前 → 「勤務外」バッジ */
    #[Test]
    public function 勤務外_UIバッジ()
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $res = $this->actingAs($u)->get(route('attendance.create'));
        $res->assertSee('勤務外');
    }

    /** 出勤中：clock_inあり／clock_outなし／休憩なし → 「勤務中」バッジ */
    #[Test]
    public function 出勤中_UIバッジ()
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        Attendance::create([
            'user_id'   => $u->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->subHour(),
            'clock_out' => null,
        ]);

        $res = $this->actingAs($u)->get(route('attendance.create'));
        $res->assertSee('出勤中');
    }

    /** 休憩中：break_times の end が null → 「休憩中」バッジ */
    #[Test]
    public function 休憩中_UIバッジ()
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $att = Attendance::create([
            'user_id'   => $u->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->subHour(),
            'clock_out' => null,
        ]);
        $att->breakTimes()->create(['start' => now()->subMinutes(10), 'end' => null]);

        $res = $this->actingAs($u)->get(route('attendance.create'));
        $res->assertSee('休憩中');
    }

    /** 退勤済：clock_out があれば「退勤済」バッジ */
    #[Test]
    public function 退勤済_UIバッジ()
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        Attendance::create([
            'user_id'   => $u->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->subHours(8),
            'clock_out' => now()->subMinute(),
        ]);

        $res = $this->actingAs($u)->get(route('attendance.create'));
        $res->assertSee('退勤済');
    }
}