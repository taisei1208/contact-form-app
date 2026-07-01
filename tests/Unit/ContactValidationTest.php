<?php

namespace Tests\Unit;

use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 問い合わせ一覧検索で正しい検索条件を受け付ける(): void
    {
        $category = Category::factory()->create();

        $request = new IndexContactRequest;

        $data = [
            'keyword' => '山田',
            'gender' => '1',
            'category_id' => $category->id,
            'date' => '2026-06-27',
        ];

        $validator = validator(
            $data,
            $request->rules(),
            $request->messages()
        );

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 問い合わせ一覧検索で不正な性別値は拒否される(): void
    {
        $request = new IndexContactRequest;

        $data = [
            'gender' => '9',
        ];

        $validator = validator(
            $data,
            $request->rules(),
            $request->messages()
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /** @test */
    public function 問い合わせ保存で全ての必須項目とタグ入力を受け付ける(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $validator = $this->makeValidator(new StoreContactRequest, [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => '1',
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->toArray(),
            'detail' => 'お問い合わせ内容です。',
        ]);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 問い合わせ保存で不正な電話番号形式は拒否される(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator(new StoreContactRequest, [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => '1',
            'email' => 'test@example.com',
            'tel' => '090-1234-5678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
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
