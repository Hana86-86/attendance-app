<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BreakFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-10-17 12:00:00');
    }

    /** 休憩機能 */
    #[Test]
    public function 休憩入は出勤中のみ_休憩中は休憩戻のみ表示_退勤前なら何度でも可能()
    {
        $u = User::factory()->create(['email_verified_at' => now()]);

        // 出勤前
        $this->actingAs($u)->post(route('attendance.break-in'))->assertStatus(302);

        // 出勤中にする
        $att = Attendance::create([
            'user_id'   => $u->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->subHours(3),
            'clock_out' => null,
        ]);

        // 休憩入 → OK（DBにend=nullの休憩が1件できる）
        $this->actingAs($u)->post(route('attendance.break-in'))->assertValid();
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $att->id,
            'end'           => null,
        ]);

        // 休憩戻 → OK（さっきのレコードのendが埋まる）
        $this->actingAs($u)->post(route('attendance.break-out'))->assertValid();
        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $att->id,
            'end'           => null,
        ]);

        // 再度休憩入（退勤前であれば何度でも
        $this->actingAs($u)->post(route('attendance.break-in'))->assertValid();
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $att->id,
            'end'           => null,
        ]);
    }
}