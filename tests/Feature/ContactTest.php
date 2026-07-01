<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問い合わせフォーム入力ページが表示される(): void
    {
        $category = Category::factory()->create();

        $tag = Tag::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    /** @test */
    public function お問い合わせ確認ページが表示される(): void
    {
        $category = Category::factory()->create([
            'content' => '商品のお届けについて',
        ]);

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $data = $this->validContactData([
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'test@example.com',
            'category_id' => $category->id,
        ], [
            $tag->id,
        ]);

        $response = $this->post('/contacts/confirm', $data);

        $response->assertStatus(200);
        $response->assertViewIs('contact.confirm');
        $response->assertSee('山田');
        $response->assertSee('太郎');
        $response->assertSee('test@example.com');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    /** @test */
    public function お問い合わせ確認でバリデーションエラー時はリダイレクトされる(): void
    {
        $data = $this->validContactData([
            'email' => 'invalid-email',
        ]);

        $response = $this->from('/')->post('/contacts/confirm', $data);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function お問い合わせを送信できる(): void
    {
        $tag = Tag::factory()->create();

        $data = $this->validContactData([
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'test@example.com',
        ], [
            $tag->id,
        ]);

        $response = $this->post('/contacts', $data);

        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'test@example.com',
        ]);

        $contact = Contact::where('email', 'test@example.com')->first();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function お問い合わせ送信でバリデーションエラー時は保存されない(): void
    {
        $data = $this->validContactData([
            'email' => 'test@example.com',
            'tel' => '090-1234-5678',
        ]);

        $response = $this->from('/')->post('/contacts', $data);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('tel');

        $this->assertDatabaseMissing('contacts', [
            'email' => 'test@example.com',
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
