<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplementalTextApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplemental_text_endpoint_returns_filtered_items(): void
    {
        $this->seed(DatabaseSeeder::class);

        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');

        DB::table('modules')->insert([
            'language_id' => $languageId,
            'type' => 'bible',
            'code' => 'L492_IBSNT',
            'name' => 'IBSNT Test',
            'short_name' => 'IBSNT',
            'is_active' => true,
            'is_public' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleId = DB::table('modules')->where('code', 'L492_IBSNT')->value('id');

        DB::table('translations')->insert([
            'module_id' => $moduleId,
            'language_id' => $languageId,
            'canon_id' => $canonId,
            'code' => 'L492_IBSNT',
            'name' => 'IBSNT Test',
            'short_name' => 'IBSNT',
            'has_new_testament' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $translationId = DB::table('translations')->where('code', 'L492_IBSNT')->value('id');

        DB::table('legacy_supplemental_texts')->insert([
            [
                'translation_id' => $translationId,
                'legacy_verse_id' => 1265822,
                'legacy_bible_id' => 492,
                'legacy_book_id' => 3978,
                'legacy_chapter_id' => 231597,
                'legacy_book_slug' => 'matthew',
                'legacy_chapter_number' => 0,
                'legacy_verse_number' => 1,
                'type' => 'heading',
                'title' => 'Intro',
                'text' => 'Евангелие Матфея начинается родословием.',
                'text_plain' => 'Евангелие Матфея начинается родословием.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'translation_id' => $translationId,
                'legacy_verse_id' => 1265900,
                'legacy_bible_id' => 492,
                'legacy_book_id' => 3979,
                'legacy_chapter_id' => 231626,
                'legacy_book_slug' => 'mark',
                'legacy_chapter_number' => 0,
                'legacy_verse_number' => 1,
                'type' => 'heading',
                'title' => 'Intro',
                'text' => 'Евангелие Марка.',
                'text_plain' => 'Евангелие Марка.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->getJson('/api/translations/L492_IBSNT/supplemental-texts?book=matthew&type=heading')
            ->assertOk()
            ->assertJsonPath('data.translation.code', 'L492_IBSNT')
            ->assertJsonPath('data.filters.book', 'matthew')
            ->assertJsonPath('data.filters.type', 'heading')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.book.slug', 'matthew')
            ->assertJsonPath('data.items.0.type', 'heading')
            ->assertJsonPath('data.items.0.title', 'Intro')
            ->assertJsonPath('data.items.0.text', 'Евангелие Матфея начинается родословием.');
    }
}
