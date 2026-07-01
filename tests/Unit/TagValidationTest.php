<?php

namespace Tests\Unit;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

class TagValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function タグ新規登録で正しいタグ名を受け付ける(): void
    {
        $validator = $this->makeStoreValidator($this->validData([
            'name' => '質問',
        ]));

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function タグ名が空だとバリデーションエラーになる(): void
    {
        $validator = $this->makeStoreValidator($this->validData([
            'name' => '',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function タグ名が50文字を超えるとバリデーションエラーになる(): void
    {
        $validator = $this->makeStoreValidator($this->validData([
            'name' => str_repeat('あ', 51),
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function 重複したタグ名は登録できない(): void
    {
        Tag::factory()->create([
            'name' => '質問',
        ]);

        $validator = $this->makeStoreValidator($this->validData([
            'name' => '質問',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function タグ更新時に自身の現在名は許可される(): void
    {
        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $validator = $this->makeUpdateValidator($this->validData([
            'name' => '質問',
        ]), $tag);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function 他で使用されているタグ名には更新できない(): void
    {
        $existingTag = Tag::factory()->create([
            'name' => '要望',
        ]);

        $targetTag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $validator = $this->makeUpdateValidator($this->validData([
            'name' => $existingTag->name,
        ]), $targetTag);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    private function makeStoreValidator(array $data)
    {
        $request = new TagRequest;

        return validator(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    private function makeUpdateValidator(array $data, Tag $tag)
    {
        $request = new TagRequest;

        $route = new Route(['PUT'], '/admin/tags/{tag}', []);
        $route->bind($request);
        $route->setParameter('tag', $tag);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return validator(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => '質問',
        ], $overrides);
    }
}
