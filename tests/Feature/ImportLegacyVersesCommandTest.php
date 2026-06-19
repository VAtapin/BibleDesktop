<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyVersesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_mapped_legacy_chapter_using_target_canonical_book_and_chapter(): void
    {
        $this->seed(DatabaseSeeder::class);

        $now = now();
        $languageId = DB::table('languages')->where('code', 'en')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $baruchId = DB::table('canonical_books')->where('slug', 'baruch')->value('id');
        $epistleId = DB::table('canonical_books')->where('slug', 'epistle')->value('id');
        $epistleChapterId = DB::table('canonical_chapters')
            ->where('canonical_book_id', $epistleId)
            ->where('number', 1)
            ->value('id');

        DB::table('modules')->insert([
            'language_id' => $languageId,
            'type' => 'bible',
            'code' => 'L10_DRB',
            'name' => 'DRB Test',
            'short_name' => 'DRB',
            'is_active' => true,
            'is_public' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleId = DB::table('modules')->where('code', 'L10_DRB')->value('id');

        DB::table('translations')->insert([
            'module_id' => $moduleId,
            'language_id' => $languageId,
            'canon_id' => $canonId,
            'code' => 'L10_DRB',
            'name' => 'DRB Test',
            'short_name' => 'DRB',
            'has_apocrypha' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $translationId = DB::table('translations')->where('code', 'L10_DRB')->value('id');

        DB::table('legacy_libraries')->insert([
            'legacy_id' => 10,
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_books')->insert([
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'canonical_book_id' => $baruchId,
            'legacy_book_id' => 678,
            'slug' => 'baruch',
            'name' => 'Baruch',
            'short_name' => 'Bar',
            'book_order' => 73,
            'chapters_count' => 6,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleBookId = DB::table('module_books')->where('legacy_book_id', 678)->value('id');

        DB::table('legacy_books')->insert([
            'legacy_id' => 678,
            'legacy_bible_id' => 10,
            'module_book_id' => $moduleBookId,
            'canonical_book_id' => $baruchId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_chapters')->insert([
            'module_book_id' => $moduleBookId,
            'canonical_chapter_id' => $epistleChapterId,
            'legacy_chapter_id' => 12213,
            'chapter_number' => 6,
            'verses_count' => 72,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleChapterId = DB::table('module_chapters')->where('legacy_chapter_id', 12213)->value('id');

        DB::table('legacy_chapters')->insert([
            'legacy_id' => 12213,
            'legacy_book_id' => 678,
            'legacy_bible_id' => 10,
            'module_chapter_id' => $moduleChapterId,
            'canonical_chapter_id' => $epistleChapterId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $path = base_path('.tmp/legacy-verses-command-test.sql');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO `verse` (`verseID`, `bookID`, `bibleID`, `chapterID`, `verseNr`, `vers`, `description`, `history`) VALUES
(321820, 678, 10, 12213, 1, '1 For the sins that you have committed before God.', '', '0000-00-00 00:00:00');
SQL);

        try {
            $this->artisan('bible:legacy:import-verses', [
                '--path' => '.tmp/legacy-verses-command-test.sql',
                '--library' => 10,
            ])->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('verses', [
            'canonical_book_id' => $epistleId,
            'canonical_chapter_id' => $epistleChapterId,
            'chapter_number' => 1,
            'verse_number' => 1,
            'osis_ref' => 'EpJer.1.1',
        ]);
        $verseId = DB::table('verses')->where('osis_ref', 'EpJer.1.1')->value('id');

        $this->assertDatabaseHas('verse_texts', [
            'verse_id' => $verseId,
            'translation_id' => $translationId,
            'module_book_id' => $moduleBookId,
            'module_chapter_id' => $moduleChapterId,
            'legacy_verse_id' => 321820,
            'text' => 'For the sins that you have committed before God.',
        ]);
        $this->assertDatabaseHas('legacy_verses', [
            'legacy_id' => 321820,
            'verse_id' => $verseId,
        ]);
    }
}
