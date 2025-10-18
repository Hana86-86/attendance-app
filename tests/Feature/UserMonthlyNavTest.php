<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserMonthlyNavTest extends TestCase
{
    use RefreshDatabase;

    /** 勤怠一覧情報取得機能　一般ユーザー */
    #[Test]
    public function 当月表示_前月_翌月へ遷移できる()
    {
        $u = User::factory()->create();
        Carbon::setTestNow('2025-10-17 10:00:00');

        // 当月
        $now = $this->actingAs($u)->get(route('attendance.list', ['month' => '2025-10']));
        $now->assertStatus(200)->assertSee('2025-10');

        // 前月
        $prev = $this->actingAs($u)->get(route('attendance.list', ['month' => '2025-09']));
        $prev->assertStatus(200)->assertSee('2025-09');

        // 翌月
        $next = $this->actingAs($u)->get(route('attendance.list', ['month' => '2025-11']));
        $next->assertStatus(200)->assertSee('2025-11');
    }
}