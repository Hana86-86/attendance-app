<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** 認証機能一般ユーザー　名前未入力 */
    #[Test]
    public function 名前が未入力だとバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    }
    /** 認証機能一般ユーザー　メールアドレス未入力 */
    #[Test]
    public function メールアドレスが未入力だとバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** 認証機能一般ユーザー　パスワードが8文字未満 */
    #[Test]
    public function パスワードが8文字未満だとバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** 認証機能一般ユーザー　パスワードが一致しない */
    #[Test]
    public function パスワードが一致しないとバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** 認証機能一般ユーザー　パスワードが未入力 */
    #[Test]
    public function パスワードが未入力だとバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** 認証機能一般ユーザー全て正しい入力 */
    #[Test]
    public function 全て正しく入力するとユーザーが登録される()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/email/verify');
    }
}

