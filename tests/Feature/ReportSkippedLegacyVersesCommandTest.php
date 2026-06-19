<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportSkippedLegacyVersesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_legacy_verses_without_canonical_chapter_mapping(): void
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
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $translationId = DB::table('translations')->where('code', 'L1_RST')->value('id');

        DB::table('legacy_libraries')->insert([
            'legacy_id' => 1,
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_books')->insert([
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'canonical_book_id' => $bookId,
            'legacy_book_id' => 10,
            'slug' => 'genesis',
            'name' => 'Бытие',
            'short_name' => 'Быт.',
            'book_order' => 1,
            'chapters_count' => 51,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleBookId = DB::table('module_books')->where('legacy_book_id', 10)->value('id');

        DB::table('legacy_books')->insert([
            'legacy_id' => 10,
            'legacy_bible_id' => 1,
            'module_book_id' => $moduleBookId,
            'canonical_book_id' => $bookId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_chapters')->insert([
            'module_book_id' => $moduleBookId,
            'canonical_chapter_id' => null,
            'legacy_chapter_id' => 100,
            'chapter_number' => 51,
            'verses_count' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleChapterId = DB::table('module_chapters')->where('legacy_chapter_id', 100)->value('id');

        DB::table('legacy_chapters')->insert([
            'legacy_id' => 100,
            'legacy_book_id' => 10,
            'legacy_bible_id' => 1,
            'module_chapter_id' => $moduleChapterId,
            'canonical_chapter_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $path = storage_path('app/skipped-report.sql');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO `verse` (`verseID`, `bookID`, `bibleID`, `chapterID`, `verseNr`, `vers`) VALUES
(1, 10, 1, 100, 1, '1 test'),
(2, 10, 1, 100, 2, '2 test');
SQL);

        $this->artisan('bible:legacy:report-skipped-verses', ['--path' => 'storage/app/skipped-report.sql'])
            ->expectsOutputToContain('Skipped legacy verses: 2')
            ->expectsOutputToContain('missing_canonical_chapter: 2')
            ->assertSuccessful();

        @unlink($path);
    }
}
