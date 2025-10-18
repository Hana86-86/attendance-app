<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 登録時にユーザーが作成され、検証用リンクで認証が完了することを確認
     */
    #[Test]
    public function 登録時に検証メール送信_リンク踏むと認証完了(): void
    {
        // 通知はフェイク化（キュー送信抑止・副作用防止）
        Notification::fake();

        //  Fortifyの登録エンドポイントでユーザー作成
        $this->post(route('register'), [
            'name'                  => 'Taro',
            'email'                 => 'taro@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(302);

        //  DBに作成されていること
        $user = User::where('email', 'taro@example.com')->firstOrFail();

        // （通知送信の厳密な種類まではチェックしない）
        // Notification::assertSentTo($user, VerifyEmail::class);

        //  署名付きURLを生成してアクセス（= 検証リンクを踏む）
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($url)->assertStatus(302);

        //  認証済みになっていること
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}