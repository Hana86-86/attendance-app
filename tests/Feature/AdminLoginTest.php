<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /** 認証機能管理者　メールアドレス、パスワード未入力 */
    #[Test]
    public function 管理者_メール未入力とパスワード未入力はバリデーションエラー()
    {
        // メール空でPOST
        $r1 = $this->post(route('admin.login.post'), ['email' => '', 'password' => 'x']);
        $r1->assertSessionHasErrors(['email']);

        // パスワード空でPOST
        $r2 = $this->post(route('admin.login.post'), ['email' => 'a@example.com', 'password' => '']);
        $r2->assertSessionHasErrors(['password']);
    }

    /** 認証機能管理者　登録内容と不一致 */
    #[Test]
    public function 管理者_未登録ではログインできず_正しい資格情報なら成功()
    {
        // 未登録でログインしようとするとエラー
        $bad = $this->post(route('admin.login.post'), ['email' => 'none@example.com', 'password' => 'x']);
        $bad->assertSessionHasErrors();

        // 登録（管理者）
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        // 正しい資格情報でログイン
        $ok = $this->post(route('admin.login.post'), ['email' => 'admin@example.com', 'password' => 'secret123']);

        // 認証済みか確認
        $this->assertAuthenticatedAs($admin);

        // 管理トップへリダイレクト
        $ok->assertRedirect(
    route('admin.attendances.index', ['date' => today()->toDateString()])
);
    }
}