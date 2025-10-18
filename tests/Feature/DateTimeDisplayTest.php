<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_現在日付がUI形式で表示される()
{
    $u = \App\Models\User::factory()->create();

    $response = $this->actingAs($u)->get(
        route('attendance.detail', ['date' => now()->toDateString()])
    );

    // UIで使っている日付表示（年と月日）を検証
    $response->assertSee(now()->isoFormat('YYYY年'));
    $response->assertSee(now()->isoFormat('M月D日'));

}
}