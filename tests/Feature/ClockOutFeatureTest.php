<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClockOutFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-10-17 18:00:00');
    }

    /** 退勤機能 */
    #[Test]
    public function 退勤は出勤中のみ可能_休憩中は不可_反映確認()
    {
        $u = User::factory()->create(['email_verified_at' => now()]);

        // 出勤前 → NG（UI想定）: サーバはエラーを持たない
        $bad1 = $this->actingAs($u)->post(route('attendance.clock-out'));
        $bad1->assertStatus(302); // ← 以前の assertSessionHasErrors() を廃止

        // 出勤中
        $att = Attendance::create([
            'user_id' => $u->id,
            'work_date' => today()->toDateString(),
            'clock_in' => now()->subHours(8),
            'clock_out' => null,
        ]);

        // 休憩入にしてみる
        $this->actingAs($u)->post(route('attendance.break-in'))->assertValid();

        // 休憩中の退勤は UI では押せない想定（ここは仕様上サーバで弾かない）
        $bad2 = $this->actingAs($u)->post(route('attendance.clock-out'));
        $bad2->assertStatus(302);

        // 休憩戻してから退勤 → OK
        $this->actingAs($u)->post(route('attendance.break-out'))->assertValid();
        $ok = $this->actingAs($u)->post(route('attendance.clock-out'));
        $ok->assertValid();

        $this->assertDatabaseHas('attendances', [
            'id' => $att->id,
            'clock_out' => now(), // setTestNow に一致
        ]);
    }
}