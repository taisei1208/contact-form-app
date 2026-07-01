<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 認証済みユーザーはタグ編集画面を表示できる(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $response = $this->actingAs($user)->get("/admin/tags/{$tag->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('質問');
    }

    /** @test */
    public function 認証済みユーザーはタグを作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', [
            'name' => '要望',
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'name' => '要望',
        ]);
    }

    /** @test */
    public function 認証済みユーザーはタグを更新できる(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $response = $this->actingAs($user)->put("/admin/tags/{$tag->id}", [
            'name' => '更新後タグ',
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '更新後タグ',
        ]);
    }

    /** @test */
    public function 認証済みユーザーはタグを削除できる(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    /** @test */
    public function 未認証ユーザーはタグ操作ができない(): void
    {
        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $this->get("/admin/tags/{$tag->id}/edit")
            ->assertRedirect('/login');

        $this->post('/admin/tags', [
            'name' => '未認証タグ',
        ])->assertRedirect('/login');

        $this->put("/admin/tags/{$tag->id}", [
            'name' => '未認証更新',
        ])->assertRedirect('/login');

        $this->delete("/admin/tags/{$tag->id}")
            ->assertRedirect('/login');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '質問',
        ]);

        $this->assertDatabaseMissing('tags', [
            'name' => '未認証タグ',
        ]);
    }
}
