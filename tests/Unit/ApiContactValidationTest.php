<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function api検索で正しいフィルタ条件を受け付ける(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator(new IndexContactRequest, [
            'keyword' => '山田',
            'gender' => '1',
            'category_id' => $category->id,
            'date' => '2026-06-27',
            'per_page' => 20,
            'page' => 1,
        ]);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function api検索で不正なフィルタ条件は拒否される(): void
    {
        $validator = $this->makeValidator(new IndexContactRequest, [
            'gender' => '9',
            'category_id' => 9999,
            'date' => 'invalid-date',
            'per_page' => 101,
            'page' => 0,
        ]);

        $this->assertFalse($validator->passes());

        $errors = $validator->errors()->toArray();

        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('date', $errors);
        $this->assertArrayHasKey('per_page', $errors);
        $this->assertArrayHasKey('page', $errors);
    }

    /** @test */
    public function api作成で正しいお問い合わせ入力を受け付ける(): void
    {
        $data = $this->validContactData();

        $validator = $this->makeValidator(new StoreContactRequest, $data);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function api作成で不正な値は拒否される(): void
    {
        $data = $this->validContactData([
            'gender' => 9,
            'tel' => '090-1234-5678',
            'category_id' => 9999,
            'tag_ids' => [9999],
        ]);

        $validator = $this->makeValidator(new StoreContactRequest, $data);

        $this->assertFalse($validator->passes());

        $errors = $validator->errors()->toArray();

        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('tel', $errors);
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('tag_ids.0', $errors);
    }

    private function validContactData(array $overrides = []): array
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        return array_merge(
            Contact::factory()->raw([
                'category_id' => $category->id,
                'tel' => '09012345678',
            ]),
            [
                'tag_ids' => $tags->pluck('id')->toArray(),
            ],
            $overrides
        );
    }

    private function makeValidator($request, array $data)
    {
        return validator(
            $data,
            $request->rules(),
            $request->messages()
        );
    }
}
