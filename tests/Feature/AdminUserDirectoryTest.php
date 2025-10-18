<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    /** ユーザー情報取得機能　管理者 */
    #[Test]
    public function 管理者はユーザー一覧で氏名とメールを確認できる()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u = User::factory()->create(['name' => '田中一郎', 'email' => 't@example.com']);

        $page = $this->actingAs($admin)->get(route('admin.users.index'));
        $page->assertSee('田中一郎')->assertSee('t@example.com');
    }
}