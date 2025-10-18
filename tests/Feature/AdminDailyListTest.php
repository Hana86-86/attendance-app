<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDailyListTest extends TestCase
{
    use RefreshDatabase;

    /** 勤怠一覧情報取得機能　管理者 */
    #[Test]
    public function 管理者は当日一覧を確認でき_前日翌日へ遷移できる()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u1 = User::factory()->create(); $u2 = User::factory()->create();
        Carbon::setTestNow('2025-10-17');

        // 当日一覧
        $page = $this->actingAs($admin)->get(route('admin.attendances.index', ['date' => '2025-10-17']));
        $page->assertStatus(200);

        // 前日
        $prev = $this->actingAs($admin)->get(route('admin.attendances.index', ['date' => '2025-10-16']));
        $prev->assertStatus(200);

        // 翌日
        $next = $this->actingAs($admin)->get(route('admin.attendances.index', ['date' => '2025-10-18']));
        $next->assertStatus(200);
    }
}