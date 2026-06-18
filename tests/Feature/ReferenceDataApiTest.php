<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_languages_endpoint_returns_active_languages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/languages')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'ru')
            ->assertJsonPath('data.4.code', 'en')
            ->assertJsonCount(5, 'data');
    }

    public function test_canon_books_endpoint_returns_orthodox_book_order(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/canons/orthodox/books')
            ->assertOk()
            ->assertJsonPath('data.code', 'orthodox')
            ->assertJsonPath('data.books.0.slug', 'genesis')
            ->assertJsonPath('data.books.0.chapters_count', 50)
            ->assertJsonPath('data.books.76.slug', 'tobit')
            ->assertJsonPath('data.books.76.is_deuterocanonical', true)
            ->assertJsonCount(77, 'data.books');
    }
}
