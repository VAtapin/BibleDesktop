<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_verse_search_returns_matching_verse_texts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $bookId = DB::table('canonical_books')->where('slug', 'genesis')->value('id');
        $chapterId = DB::table('canonical_chapters')
            ->where('canonical_book_id', $bookId)
            ->where('number', 1)
            ->value('id');

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
        $moduleBookId = DB::table('module_books')->where('slug', 'genesis')->value('id');

        DB::table('module_chapters')->insert([
            'module_book_id' => $moduleBookId,
            'canonical_chapter_id' => $chapterId,
            'chapter_number' => 1,
            'verses_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleChapterId = DB::table('module_chapters')->where('module_book_id', $moduleBookId)->value('id');

        DB::table('verses')->insert([
            'canonical_book_id' => $bookId,
            'canonical_chapter_id' => $chapterId,
            'chapter_number' => 1,
            'verse_number' => 1,
            'osis_ref' => 'Gen.1.1',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $verseId = DB::table('verses')->where('osis_ref', 'Gen.1.1')->value('id');

        DB::table('verse_texts')->insert([
            'verse_id' => $verseId,
            'translation_id' => $translationId,
            'module_book_id' => $moduleBookId,
            'module_chapter_id' => $moduleChapterId,
            'text' => 'В начале сотворил Бог небо и землю.',
            'text_plain' => 'В начале сотворил Бог небо и землю.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('verses')->insert([
            'canonical_book_id' => $bookId,
            'canonical_chapter_id' => $chapterId,
            'chapter_number' => 1,
            'verse_number' => 2,
            'osis_ref' => 'Gen.1.2',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $secondVerseId = DB::table('verses')->where('osis_ref', 'Gen.1.2')->value('id');

        DB::table('verse_texts')->insert([
            'verse_id' => $secondVerseId,
            'translation_id' => $translationId,
            'module_book_id' => $moduleBookId,
            'module_chapter_id' => $moduleChapterId,
            'text' => 'Мария слушала слово.',
            'text_plain' => 'Мария слушала слово.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->getJson('/api/search/verses?q=%D1%81%D0%BE%D1%82%D0%B2%D0%BE%D1%80%D0%B8%D0%BB&translation=L1_RST')
            ->assertOk()
            ->assertJsonPath('data.query', 'сотворил')
            ->assertJsonPath('data.mode', 'text')
            ->assertJsonPath('data.results.0.osis_ref', 'Gen.1.1')
            ->assertJsonPath('data.results.0.translation.code', 'L1_RST')
            ->assertJsonPath('data.results.0.snippet_segments.1.text', 'сотворил')
            ->assertJsonPath('data.results.0.snippet_segments.1.match', true);

        $this->getJson('/api/search/verses?q=Gen.1.1&translation=L1_RST')
            ->assertOk()
            ->assertJsonPath('data.mode', 'reference')
            ->assertJsonPath('data.results.0.osis_ref', 'Gen.1.1');

        $this->getJson('/api/search/verses?q=%D0%91%D1%8B%D1%82.%201%3A1&translation=L1_RST')
            ->assertOk()
            ->assertJsonPath('data.mode', 'reference')
            ->assertJsonPath('data.results.0.osis_ref', 'Gen.1.1');

        $this->getJson('/api/search/verses?q=%D0%9C%D0%B8%D1%80%D0%B8&translation=L1_RST&match=fuzzy')
            ->assertOk()
            ->assertJsonPath('data.match', 'fuzzy')
            ->assertJsonPath('data.results.0.osis_ref', 'Gen.1.2');
    }
}
