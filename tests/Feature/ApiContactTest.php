<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問い合わせ一覧apiでjson形式の一覧とmeta情報が返る(): void
    {
        Category::factory()->create();
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=2');

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'category',
                    'first_name',
                    'last_name',
                    'gender',
                    'email',
                    'tel',
                    'address',
                    'building',
                    'detail',
                    'tags',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function お問い合わせ一覧apiで検索条件に一致するデータだけ返る(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        Contact::factory()->create([
            'last_name' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'email' => 'target@example.com',
            'created_at' => '2026-06-27 10:00:00',
        ]);

        Contact::factory()->count(4)->sequence(
            [
                'last_name' => '佐藤',
                'gender' => 1,
                'category_id' => $category->id,
                'email' => 'keyword-out@example.com',
                'created_at' => '2026-06-27 10:00:00',
            ],
            [
                'last_name' => '山田',
                'gender' => 2,
                'category_id' => $category->id,
                'email' => 'gender-out@example.com',
                'created_at' => '2026-06-27 10:00:00',
            ],
            [
                'last_name' => '山田',
                'gender' => 1,
                'category_id' => $otherCategory->id,
                'email' => 'category-out@example.com',
                'created_at' => '2026-06-27 10:00:00',
            ],
            [
                'last_name' => '山田',
                'gender' => 1,
                'category_id' => $category->id,
                'email' => 'date-out@example.com',
                'created_at' => '2026-06-28 10:00:00',
            ],
        )->create();

        $response = $this->getJson('/api/v1/contacts?'.http_build_query([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-06-27',
        ]));

        $response->assertOk();

        $emails = collect($response->json('data'))->pluck('email');

        $this->assertTrue($emails->contains('target@example.com'));
        $this->assertFalse($emails->contains('keyword-out@example.com'));
        $this->assertFalse($emails->contains('gender-out@example.com'));
        $this->assertFalse($emails->contains('category-out@example.com'));
        $this->assertFalse($emails->contains('date-out@example.com'));
    }

    /** @test */
    public function お問い合わせ一覧apiでバリデーションエラー時は422が返る(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=9&category_id=9999');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gender', 'category_id']);
        $response->assertJsonPath('errors.gender.0', '性別の値が不正です');
        $response->assertJsonPath('errors.category_id.0', '選択されたカテゴリーが存在しません');
    }

    /** @test */
    public function お問い合わせ詳細apiでjson形式の詳細が返る(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'detail@example.com',
        ]);

        $contact->tags()->attach($tag->id);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.email', 'detail@example.com');
        $response->assertJsonPath('data.category.id', $category->id);
        $response->assertJsonPath('data.tags.0.id', $tag->id);
    }

    /** @test */
    public function お問い合わせ詳細apiで存在しないidの場合は404エラーjsonが返る(): void
    {
        $response = $this->getJson('/api/v1/contacts/9999');

        $response->assertNotFound();
        $response->assertJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    /** @test */
    public function お問い合わせ作成apiでレコードが作成され201が返る(): void
    {
        $tag = Tag::factory()->create();

        $data = $this->validContactData([
            'email' => 'store@example.com',
        ], [
            $tag->id,
        ]);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(201);
        $response->assertJsonPath('data.email', 'store@example.com');
        $response->assertJsonPath('data.tags.0.id', $tag->id);

        $this->assertDatabaseHas('contacts', [
            'email' => 'store@example.com',
        ]);

        $contact = Contact::where('email', 'store@example.com')->first();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function お問い合わせ作成apiでバリデーションエラー時は422が返る(): void
    {
        $data = $this->validContactData([
            'tel' => '090-1234-5678',
            'gender' => 9,
            'category_id' => 9999,
            'tag_ids' => [9999],
        ]);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tel',
            'gender',
            'category_id',
            'tag_ids.0',
        ]);
        $response->assertJsonPath('errors.tel.0', '電話番号はハイフンなしの10〜11桁で入力してください');
        $response->assertJsonPath('errors.gender.0', '性別の値が不正です');
        $response->assertJsonPath('errors.category_id.0', '選択されたカテゴリーが存在しません');
        $this->assertSame(
            '選択されたタグが存在しません',
            $response->json('errors')['tag_ids.0'][0]
        );
    }

    /** @test */
    public function お問い合わせ更新apiでレコードが更新され200が返る(): void
    {
        Category::factory()->create();
        $contact = Contact::factory()->create([
            'email' => 'before@example.com',
        ]);

        $tag = Tag::factory()->create();

        $data = $this->validContactData([
            'email' => 'after@example.com',
            'last_name' => '更新後',
        ], [
            $tag->id,
        ]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertOk();
        $response->assertJsonPath('data.email', 'after@example.com');
        $response->assertJsonPath('data.last_name', '更新後');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'after@example.com',
            'last_name' => '更新後',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function お問い合わせ更新apiで存在しないidの場合は404が返る(): void
    {
        $data = $this->validContactData();

        $response = $this->putJson('/api/v1/contacts/9999', $data);

        $response->assertNotFound();
        $response->assertJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    /** @test */
    public function お問い合わせ更新apiでバリデーションエラー時は422が返る(): void
    {
        Category::factory()->create();
        $contact = Contact::factory()->create();

        $data = $this->validContactData([
            'tel' => '090-1234-5678',
        ]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tel']);
        $response->assertJsonPath('errors.tel.0', '電話番号はハイフンなしの10〜11桁で入力してください');
    }

    /** @test */
    public function お問い合わせ削除apiでレコードが削除され204が返る(): void
    {
        Category::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function お問い合わせ削除apiで存在しないidの場合は404が返る(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/9999');

        $response->assertNotFound();
        $response->assertJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    private function validContactData(array $overrides = [], array $tagIds = []): array
    {
        $category = Category::factory()->create();

        return array_merge(
            Contact::factory()->raw([
                'category_id' => $category->id,
                'tel' => '09012345678',
            ]),
            [
                'tag_ids' => $tagIds,
            ],
            $overrides
        );
    }
}
