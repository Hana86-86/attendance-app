<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserStampCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 備考が必須であること_新仕様の入力名で検証する()
    {
        // メール認証済みユーザーを作成してログイン
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // 当日の勤怠を作成（出勤09:00〜退勤18:00）
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2025-10-17',
            'clock_in'  => '2025-10-17 09:00:00',
            'clock_out' => '2025-10-17 18:00:00',
        ]);

        // 備考（reason）を空にしてバリデーションエラーを確認
        $this->post(route('requests.store'), [
            'date'      => '2025-10-17',
            'clock_out' => '17:59',
            // 'reason' をあえて空にする
            'reason'    => '',
        ])->assertSessionHasErrors([
            'reason',
        ]);
    }

    #[Test]
    public function 不正な退勤時刻は翻訳キー通りのエラーになること()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2025-10-17',
            'clock_in'  => '2025-10-17 09:00:00',
            'clock_out' => '2025-10-17 18:00:00',
        ]);

        //  逆転 clock_out_invalid
        $res = $this->post(route('requests.store'), [
            'date'       => '2025-10-17',
            'clock_in'   => '18:00',
            'clock_out'  => '17:59',
            'reason'     => '時刻テスト',
        ]);

        // フィールドとして clock_out がエラーであること
        $res->assertSessionHasErrors(['clock_out']);
        // エラーメッセージが翻訳キー通りであること
        $errors = session('errors')->get('clock_out');
        $this->assertTrue(
            collect($errors)->contains(fn($m) => str_contains($m, __('validation.attendance.common.clock_out_invalid')))
        );
    }

    /**
     * 申請成功で承認待ちに登録 → 一覧に自分の申請が全て表示
     * - 同日の pending が無ければ登録できる
     * - 別日についても登録でき、一覧に両方の理由が見える
     */
    #[Test]
    public function 申請成功で承認待ちに登録_一覧に自分の申請が全て表示()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // 対象日①（12:00–13:00の勤務）
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2025-10-20',
            'clock_in'  => '2025-10-20 12:00:00',
            'clock_out' => '2025-10-20 13:00:00',
        ]);

        // 休憩修正を breaks[0] で送る
        $this->post(route('requests.store'), [
            'date'   => '2025-10-20',
            'breaks' => [
                ['start' => '12:10', 'end' => '12:40'],
            ],
            'reason' => '昼休み修正',
        ])->assertValid();

        // 対象日②（09:00–18:00の勤務）
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2025-10-19',
            'clock_in'  => '2025-10-19 09:00:00',
            'clock_out' => '2025-10-19 18:00:00',
        ]);

        // 退勤時刻の修正だけ送る
        $this->post(route('requests.store'), [
            'date'      => '2025-10-19',
            'clock_out' => '17:30',
            'reason'    => '退勤が早かった',
        ])->assertValid();

        // 一覧に2件とも自分の申請理由が出ること
        $index = $this->get(route('requests.list'));
        $index->assertSee('昼休み修正');
        $index->assertSee('退勤が早かった');
    }
}