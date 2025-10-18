<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCorrectionApproveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者：承認待ち一覧に pending が全件表示される
     */
    #[Test]
    public function 承認待ち一覧に全件表示()
    {
        // 管理者は verified 必須
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $u1 = User::factory()->create(['email_verified_at' => now()]);
        $u2 = User::factory()->create(['email_verified_at' => now()]);

        // ひも付く勤怠（適当でOK）
        $a1 = Attendance::create([
            'user_id' => $u1->id, 'work_date' => '2025-10-17',
            'clock_in' => '2025-10-17 09:00:00', 'clock_out' => '2025-10-17 18:00:00',
        ]);
        $a2 = Attendance::create([
            'user_id' => $u2->id, 'work_date' => '2025-10-17',
            'clock_in' => '2025-10-17 09:00:00', 'clock_out' => '2025-10-17 18:00:00',
        ]);

        // Factory を使わず create でOK（モデル側の $fillable が前提）
        StampCorrectionRequest::create([
            'user_id' => $u1->id, 'attendance_id' => $a1->id,
            'status' => 'pending', 'reason' => 'u1の申請A',
        ]);
        StampCorrectionRequest::create([
            'user_id' => $u2->id, 'attendance_id' => $a2->id,
            'status' => 'pending', 'reason' => 'u2の申請B',
        ]);

        $page = $this->actingAs($admin)->get(route('admin.requests.index')); // デフォルトは pending
        $page->assertStatus(200)
            ->assertSee('u1の申請A')
            ->assertSee('u2の申請B');
    }

    /**
     * 管理者：承認ボタンで申請が approved になり、勤怠も更新される
     */
    #[Test]
    public function 承認ボタンで申請がapprovedになり勤怠も更新()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $user  = User::factory()->create(['email_verified_at' => now()]);

        // 当日勤怠（退勤未記録）
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2025-10-17',
            'clock_in'  => '2025-10-17 09:00:00',
            'clock_out' => null,
        ]);

        // 退勤時刻の修正申請（pending）
        $req = StampCorrectionRequest::create([
            'user_id'             => $user->id,
            'attendance_id'       => $attendance->id,
            'requested_clock_out' => '2025-10-17 18:05:00',
            'reason'              => '退勤を付け忘れ',
            'status'              => 'pending',
        ]);

        // 管理者が承認
        $res = $this->actingAs($admin)->post(route('admin.requests.approve'), [
            'id' => $req->id,
        ]);
        $res->assertValid();

        // 申請が approved
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $req->id, 'status' => 'approved',
        ]);
        // 勤怠が更新
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id, 'clock_out' => '2025-10-17 18:05:00',
        ]);
    }

    /**
     * 管理者：承認済み一覧に approved の申請が表示される
     */
    #[Test]
    public function 承認済み一覧に承認済みの申請が表示()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $user  = User::factory()->create(['email_verified_at' => now()]);

        $att = Attendance::create([
            'user_id' => $user->id, 'work_date' => '2025-10-17',
            'clock_in' => '2025-10-17 09:00:00', 'clock_out' => '2025-10-17 18:00:00',
        ]);

        StampCorrectionRequest::create([
            'user_id' => $user->id, 'attendance_id' => $att->id,
            'status'  => 'approved', 'reason'  => '承認済みの表示確認',
        ]);

        // ★ controller はデフォで pending を出す実装なので、approved タブを明示
        $page = $this->actingAs($admin)->get(
            route('admin.requests.index', ['status' => 'approved'])
        );
        $page->assertStatus(200)->assertSee('承認済みの表示確認');
    }
}