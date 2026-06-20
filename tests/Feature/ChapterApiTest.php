<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChapterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_endpoint_returns_translation_verses(): void
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
            'has_new_testament' => true,
            'has_apocrypha' => true,
            'has_strong' => true,
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

        DB::table('strong_lexicons')->insert([
            'code' => 'HEB',
            'name' => 'Hebrew',
            'language' => 'he',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $lexiconId = DB::table('strong_lexicons')->where('code', 'HEB')->value('id');

        DB::table('strong_entries')->insert([
            'strong_lexicon_id' => $lexiconId,
            'number' => 'H7225',
            'word' => 'רֵאשִׁית',
            'transliteration' => 'reshith',
            'content' => 'beginning',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $entryId = DB::table('strong_entries')->where('number', 'H7225')->value('id');

        DB::table('verse_texts')->insert([
            'verse_id' => $verseId,
            'translation_id' => $translationId,
            'module_book_id' => $moduleBookId,
            'module_chapter_id' => $moduleChapterId,
            'text' => 'В начале сотворил Бог небо и землю.',
            'text_plain' => 'В начале сотворил Бог небо и землю.',
            'text_raw' => '1 В начале H7225 сотворил H1254 Бог H0430 небо H8064 и землю H0776.',
            'has_strong_markup' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $verseTextId = DB::table('verse_texts')->where('verse_id', $verseId)->value('id');

        DB::table('verse_strong_tokens')->insert([
            'verse_text_id' => $verseTextId,
            'verse_id' => $verseId,
            'strong_entry_id' => $entryId,
            'strong_number' => 'H7225',
            'token_order' => 1,
            'surface_text' => 'начале',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->getJson('/api/translations/L1_RST/books/genesis/chapters/1')
            ->assertOk()
            ->assertJsonPath('data.translation.code', 'L1_RST')
            ->assertJsonPath('data.book.slug', 'genesis')
            ->assertJsonPath('data.chapter.number', 1)
            ->assertJsonPath('data.verses.0.number', 1)
            ->assertJsonPath('data.verses.0.osis_ref', 'Gen.1.1')
            ->assertJsonPath('data.verses.0.text', 'В начале сотворил Бог небо и землю.')
            ->assertJsonPath('data.verses.0.has_strong_markup', true)
            ->assertJsonPath('data.verses.0.strong_tokens.0.strong_number', 'H7225')
            ->assertJsonPath('data.verses.0.strong_tokens.0.surface_text', 'начале');
    }
}
