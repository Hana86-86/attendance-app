<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClockInFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-10-17 09:00:00');
    }

    /** 出勤機能 */
    #[Test]
    public function 出勤は一度だけ可能_一覧に反映される()
    {
        $u = User::factory()->create(['email_verified_at' => now()]);

        // 1回目：成功（レコードができる）
        $this->actingAs($u)->post(route('attendance.clock-in'))->assertValid();
        $this->assertDatabaseHas('attendances', [
            'user_id'   => $u->id,
            'work_date' => today()->toDateString(),
            'clock_out' => null,
        ]);

        // 2回目：UIでは押せない（サーバはエラー返さない）
        $this->actingAs($u)->post(route('attendance.clock-in'))->assertStatus(302);
    }
}