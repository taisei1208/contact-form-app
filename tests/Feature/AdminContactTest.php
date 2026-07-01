<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 認証済みユーザーは管理画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    }

    /** @test */
    public function 未認証ユーザーは管理画面にアクセスできない(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function 管理画面で検索条件に一致するお問い合わせだけ表示される(): void
    {
        $user = User::factory()->create();

        Category::factory()->create();

        Contact::factory()->create([
            'last_name' => '山田',
            'email' => 'yamada@example.com',
        ]);

        Contact::factory()->create([
            'last_name' => '佐藤',
            'email' => 'sato@example.com',
        ]);

        $response = $this->actingAs($user)->get('/admin?'.http_build_query([
            'keyword' => '山田',
        ]));

        $response->assertStatus(200);
        $response->assertSee('yamada@example.com');
        $response->assertDontSee('sato@example.com');
    }

    /** @test */
    public function 管理画面でお問い合わせが7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        Category::factory()->create();

        Contact::factory()
            ->count(8)
            ->sequence(fn ($sequence) => [
                'last_name' => '山田',
                'email' => 'contact'.($sequence->index + 1).'@example.com',
            ])
            ->create();

        $response = $this->actingAs($user)->get('/admin?keyword=山田');

        $response->assertStatus(200);
        $response->assertSee('contact1@example.com');
        $response->assertSee('contact7@example.com');
        $response->assertDontSee('contact8@example.com');
    }

    /** @test */
    public function お問合せ詳細ページが表示される(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'detail@example.com',
        ]);

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertSee('detail@example.com');
        $response->assertSee($category->content);
    }

    /** @test */
    public function お問合せを削除できる(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function ログイン済み管理者はフィルタ条件付きでcsvをダウンロードできる(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();

        Contact::factory()->create([
            'email' => 'target@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('contacts.export'));

        $response->assertStatus(200);

        $csv = $response->streamedContent();

        $this->assertStringContainsString('target@example.com', $csv);
    }

    /** @test */
    public function csvエクスポートで条件無指定時は新着順で出力される(): void
    {
        $user = User::factory()->create();

        Category::factory()->create();

        Contact::factory()->create([
            'email' => 'old@example.com',
            'created_at' => '2026-06-25 10:00:00',
        ]);

        Contact::factory()->create([
            'email' => 'middle@example.com',
            'created_at' => '2026-06-26 10:00:00',
        ]);

        Contact::factory()->create([
            'email' => 'new@example.com',
            'created_at' => '2026-06-27 10:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('contacts.export'));

        $response->assertStatus(200);

        $csv = $response->streamedContent();

        $this->assertStringContainsString('new@example.com', $csv);
        $this->assertStringContainsString('middle@example.com', $csv);
        $this->assertStringContainsString('old@example.com', $csv);

        $newPosition = strpos($csv, 'new@example.com');
        $middlePosition = strpos($csv, 'middle@example.com');
        $oldPosition = strpos($csv, 'old@example.com');

        $this->assertLessThan($middlePosition, $newPosition);
        $this->assertLessThan($oldPosition, $middlePosition);
    }
}
