<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tagIds = Tag::pluck('id')->toArray();

        Contact::factory()
            ->count(20)
            ->create()
            ->each(function (Contact $contact) use ($tagIds) {
                $contact->tags()->attach(
                    fake()->randomElements($tagIds, random_int(1, 3))
                );
            });
    }
}
