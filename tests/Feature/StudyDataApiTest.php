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

class StudyDataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_study_data_endpoints_return_strong_tokens_and_cross_references(): void
    {
        $this->seed(DatabaseSeeder::class);

        $ids = $this->createReaderFixture();
        $now = now();

        DB::table('strong_lexicons')->insert([
            'code' => 'HEB',
            'name' => 'Hebrew',
            'language' => 'Hebrew',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $lexiconId = DB::table('strong_lexicons')->where('code', 'HEB')->value('id');

        DB::table('strong_entries')->insert([
            'strong_lexicon_id' => $lexiconId,
            'number' => 'H7225',
            'word' => 'רֵאשִׁית',
            'transliteration' => 'rêshı̂yth',
            'content' => 'beginning',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('cross_references')->insert([
            'source_verse_id' => $ids['source_verse_id'],
            'target_verse_id' => $ids['target_verse_id'],
            'type' => 'tsk',
            'source' => 'legacy_quote',
            'metadata_json' => json_encode(['raw_ref' => 'Joh 1:1'], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->getJson('/api/strong/H7225')
            ->assertOk()
            ->assertJsonPath('data.number', 'H7225')
            ->assertJsonPath('data.lexicon.code', 'HEB');

        $this->getJson("/api/strong/7225?verse={$ids['source_verse_id']}")
            ->assertOk()
            ->assertJsonPath('data.number', 'H7225')
            ->assertJsonPath('data.lexicon.code', 'HEB');

        $this->getJson("/api/verses/{$ids['source_verse_id']}/strong-tokens")
            ->assertOk()
            ->assertJsonPath('data.verse.osis_ref', 'Gen.1.1')
            ->assertJsonPath('data.tokens.0.strong_number', 'H7225')
            ->assertJsonPath('data.tokens.0.entry.transliteration', null);

        $this->getJson("/api/verses/{$ids['source_verse_id']}/cross-references?translation=L1_RST")
            ->assertOk()
            ->assertJsonPath('data.references.0.target.osis_ref', 'John.1.1')
            ->assertJsonPath('data.references.0.target.text', 'В начале было Слово.');
    }

    /**
     * @return array{source_verse_id: int, target_verse_id: int, source_verse_text_id: int}
     */
    private function createReaderFixture(): array
    {
        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $genesisId = DB::table('canonical_books')->where('slug', 'genesis')->value('id');
        $johnId = DB::table('canonical_books')->where('slug', 'john')->value('id');
        $genesisChapterId = DB::table('canonical_chapters')
            ->where('canonical_book_id', $genesisId)
            ->where('number', 1)
            ->value('id');
        $johnChapterId = DB::table('canonical_chapters')
            ->where('canonical_book_id', $johnId)
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
            [
                'module_id' => $moduleId,
                'translation_id' => $translationId,
                'canonical_book_id' => $genesisId,
                'slug' => 'genesis',
                'name' => 'Бытие',
                'short_name' => 'Быт.',
                'book_order' => 1,
                'chapters_count' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module_id' => $moduleId,
                'translation_id' => $translationId,
                'canonical_book_id' => $johnId,
                'slug' => 'john',
                'name' => 'От Иоанна',
                'short_name' => 'Ин.',
                'book_order' => 43,
                'chapters_count' => 21,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $genesisModuleBookId = DB::table('module_books')->where('slug', 'genesis')->value('id');
        $johnModuleBookId = DB::table('module_books')->where('slug', 'john')->value('id');

        DB::table('module_chapters')->insert([
            [
                'module_book_id' => $genesisModuleBookId,
                'canonical_chapter_id' => $genesisChapterId,
                'chapter_number' => 1,
                'verses_count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module_book_id' => $johnModuleBookId,
                'canonical_chapter_id' => $johnChapterId,
                'chapter_number' => 1,
                'verses_count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $genesisModuleChapterId = DB::table('module_chapters')->where('module_book_id', $genesisModuleBookId)->value('id');
        $johnModuleChapterId = DB::table('module_chapters')->where('module_book_id', $johnModuleBookId)->value('id');

        DB::table('verses')->insert([
            [
                'canonical_book_id' => $genesisId,
                'canonical_chapter_id' => $genesisChapterId,
                'chapter_number' => 1,
                'verse_number' => 1,
                'osis_ref' => 'Gen.1.1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'canonical_book_id' => $johnId,
                'canonical_chapter_id' => $johnChapterId,
                'chapter_number' => 1,
                'verse_number' => 1,
                'osis_ref' => 'John.1.1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $sourceVerseId = DB::table('verses')->where('osis_ref', 'Gen.1.1')->value('id');
        $targetVerseId = DB::table('verses')->where('osis_ref', 'John.1.1')->value('id');

        DB::table('verse_texts')->insert([
            [
                'verse_id' => $sourceVerseId,
                'translation_id' => $translationId,
                'module_book_id' => $genesisModuleBookId,
                'module_chapter_id' => $genesisModuleChapterId,
                'text' => 'В начале H7225 сотворил Бог небо и землю.',
                'has_strong_markup' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'verse_id' => $targetVerseId,
                'translation_id' => $translationId,
                'module_book_id' => $johnModuleBookId,
                'module_chapter_id' => $johnModuleChapterId,
                'text' => 'В начале было Слово.',
                'has_strong_markup' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        return [
            'source_verse_id' => $sourceVerseId,
            'target_verse_id' => $targetVerseId,
            'source_verse_text_id' => DB::table('verse_texts')->where('verse_id', $sourceVerseId)->value('id'),
        ];
    }
}
