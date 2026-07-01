<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function カテゴリから紐づく複数のお問い合わせを取得できる(): void
    {
        $category = Category::factory()->create();

        $contact1 = $this->createContact($category);
        $contact2 = $this->createContact($category);

        $category = $category->fresh(['contacts']);

        $this->assertCount(2, $category->contacts);
        $this->assertTrue($category->contacts->contains('id', $contact1->id));
        $this->assertTrue($category->contacts->contains('id', $contact2->id));
    }

    /** @test */
    public function お問い合わせは特定のカテゴリに属している(): void
    {
        $category = Category::factory()->create();

        $contact = $this->createContact($category);

        $contact = $contact->fresh(['category']);

        $this->assertTrue($contact->category->is($category));
    }

    /** @test */
    public function お問い合わせは複数のタグと同期できる(): void
    {
        $category = Category::factory()->create();

        $contact = $this->createContact($category);

        $tags = Tag::factory()->count(2)->create();

        $contact->tags()->sync($tags->pluck('id')->toArray());

        $contact = $contact->fresh(['tags']);

        $this->assertCount(2, $contact->tags);

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    /** @test */
    public function タグは中間テーブルを介して複数のお問い合わせに紐づいている(): void
    {
        $category = Category::factory()->create();

        $tag = Tag::factory()->create();

        $contact1 = $this->createContact($category);
        $contact2 = $this->createContact($category);

        $tag->contacts()->attach([
            $contact1->id,
            $contact2->id,
        ]);

        $tag = $tag->fresh(['contacts']);

        $this->assertCount(2, $tag->contacts);
        $this->assertTrue($tag->contacts->contains('id', $contact1->id));
        $this->assertTrue($tag->contacts->contains('id', $contact2->id));
    }

    private function createContact(Category $category): Contact
    {
        return Contact::factory()->create([
            'category_id' => $category->id,
        ]);
    }
}
