<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Requests\StoreContactRequest;
use App\Models\Tag;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
class ContactValidationTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
