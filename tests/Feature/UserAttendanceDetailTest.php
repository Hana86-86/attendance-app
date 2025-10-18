<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** 勤怠詳細情報取得機能　一般ユーザー */
    #[Test]
    public function 画面の氏名_日付_出退勤_休憩時間がDBと一致する()
    {
        $u = User::factory()->create([
    'email_verified_at' => now(),
    'name' => '山田花子',
]);

        $att = Attendance::create([
            'user_id' => $u->id,
            'work_date' => '2025-10-17',
            'clock_in' => '2025-10-17 09:00:00',
            'clock_out' => '2025-10-17 18:00:00',
        ]);
        $att->breakTimes()->create([
            'start' => '2025-10-17 12:00:00',
            'end'   => '2025-10-17 12:30:00',
        ]);

        $page = $this->actingAs($u)->get(route('attendance.detail', ['date' => '2025-10-17']));
        $page->assertSee('山田花子')
            ->assertSee('2025-10-17')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('12:00')
            ->assertSee('12:30');
    }
}