<?php

/**
 * BibleDesktop - Bible study desktop and web application.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 *
 * @link https://bible-desktop.com/
 *
 * @copyright 2026 Atapin Vladimir / Bible Media
 *
 * @version 1.0.0
 */

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ->assertJsonCount(26, 'data');
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

    public function test_translations_endpoint_returns_active_module_translations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');

        DB::table('modules')->insert([
            'language_id' => $languageId,
            'type' => 'bible',
            'code' => 'L1_RST',
            'name' => 'Russian Synodal Test',
            'short_name' => 'RST',
            'is_active' => true,
            'is_public' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleId = DB::table('modules')->where('code', 'L1_RST')->value('id');

        DB::table('translations')->insert([
            'module_id' => $moduleId,
            'language_id' => $languageId,
            'canon_id' => $canonId,
            'code' => 'L1_RST',
            'name' => 'Russian Synodal Test',
            'short_name' => 'RST',
            'has_old_testament' => true,
            'has_new_testament' => true,
            'has_apocrypha' => true,
            'has_strong' => true,
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->getJson('/api/translations')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'L1_RST')
            ->assertJsonPath('data.0.language.code', 'ru')
            ->assertJsonPath('data.0.canon_code', 'orthodox')
            ->assertJsonPath('data.0.has_strong', true)
            ->assertJsonPath('data.0.is_default', true);
    }

    public function test_translation_books_endpoint_returns_module_book_names(): void
    {
        $this->seed(DatabaseSeeder::class);

        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $bookId = DB::table('canonical_books')->where('slug', 'genesis')->value('id');

        DB::table('modules')->insert([
            'language_id' => $languageId,
            'type' => 'bible',
            'code' => 'L1_RST',
            'name' => 'Russian Synodal Test',
            'short_name' => 'RST',
            'is_active' => true,
            'is_public' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleId = DB::table('modules')->where('code', 'L1_RST')->value('id');

        DB::table('translations')->insert([
            'module_id' => $moduleId,
            'language_id' => $languageId,
            'canon_id' => $canonId,
            'code' => 'L1_RST',
            'name' => 'Russian Synodal Test',
            'short_name' => 'RST',
            'has_old_testament' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $translationId = DB::table('translations')->where('code', 'L1_RST')->value('id');

        DB::table('module_books')->insert([
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'canonical_book_id' => $bookId,
            'slug' => 'genesis',
            'name' => 'Бытие',
            'short_name' => 'Быт.',
            'book_order' => 1,
            'chapters_count' => 50,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->getJson('/api/translations/L1_RST/books')
            ->assertOk()
            ->assertJsonPath('data.translation.code', 'L1_RST')
            ->assertJsonPath('data.books.0.slug', 'genesis')
            ->assertJsonPath('data.books.0.name', 'Бытие')
            ->assertJsonPath('data.books.0.canonical_book.osis_code', 'Gen');
    }
}
