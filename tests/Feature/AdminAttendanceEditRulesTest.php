<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAttendanceEditRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 詳細画面に出すデータが選択したものと一致していることを確認。
     * 勤怠詳細情報取得・修正機能(管理者)
     */
    #[Test]
    public function 詳細画面に選択データが表示される()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $user  = User::factory()->create(['name' => '佐藤管理対象']);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2025-10-17',
            'clock_in'  => '2025-10-17 09:00:00',
            'clock_out' => '2025-10-17 18:00:00',
        ]);

        $page = $this->actingAs($admin)->get(
            route('admin.attendances.show', ['date' => '2025-10-17', 'id' => $user->id])
        );

        $page->assertStatus(200)
            ->assertSee('佐藤管理対象')
            ->assertSee('2025-10-17')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /**
     * 出退勤・休憩の不正値と備考未入力で、エラーメッセージ。
     */
    #[Test]
    public function 管理者更新_不正時刻と備考未入力でエラーメッセージ()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $user  = User::factory()->create();

        $res = $this->actingAs($admin)->post(
    route('admin.attendances.update', ['date' => '2025-10-17', 'id' => $user->id]),
    [
        'clock_in'  => '10:00',
        'clock_out' => '09:00', // 逆転
        'breaks'    => [
            ['start' => '14:00', 'end' => '13:59'], // 逆転
        ],
        'reason'    => '',
    ]
);

$res->assertSessionHasErrors([
    'clock_out'        => '出勤時間もしくは退勤時間が不適切な値です',
    'breaks.0.start'   => '休憩時間が不適切な値です',
    'breaks.0.end'     => '休憩時間もしくは退勤時間が不適切な値です',
    'reason'           => '備考を記入してください',
]);
    }
}