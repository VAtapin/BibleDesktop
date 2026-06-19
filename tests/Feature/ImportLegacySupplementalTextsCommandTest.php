<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacySupplementalTextsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_heading_override_verses_as_supplemental_texts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->createLegacyHeadingFixture();

        $path = base_path('.tmp/legacy-supplemental-command-test.sql');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO `verse` (`verseID`, `bookID`, `bibleID`, `chapterID`, `verseNr`, `vers`, `description`, `history`) VALUES
(1265822, 3978, 492, 231597, 1, '0 Евангелие Матфея начинается родословием.<h6>Родословие Иисуса</h6>', '', '0000-00-00 00:00:00');
SQL);

        try {
            $this->artisan('bible:legacy:import-supplemental-texts', [
                '--path' => '.tmp/legacy-supplemental-command-test.sql',
                '--types' => 'heading',
            ])
                ->expectsOutputToContain('Imported supplemental legacy texts: 1 rows.')
                ->assertSuccessful();

            $this->artisan('bible:legacy:import-supplemental-texts', [
                '--path' => '.tmp/legacy-supplemental-command-test.sql',
                '--types' => 'heading',
            ])->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, DB::table('legacy_supplemental_texts')->count());
        $this->assertDatabaseHas('legacy_supplemental_texts', [
            'legacy_verse_id' => 1265822,
            'legacy_bible_id' => 492,
            'legacy_book_id' => 3978,
            'legacy_chapter_id' => 231597,
            'legacy_book_slug' => 'matthew',
            'legacy_chapter_number' => 0,
            'legacy_verse_number' => 1,
            'type' => 'heading',
            'title' => 'Intro',
            'text' => 'Евангелие Матфея начинается родословием.<h6>Родословие Иисуса</h6>',
            'text_plain' => 'Евангелие Матфея начинается родословием.Родословие Иисуса',
        ]);
    }

    public function test_it_imports_requires_book_mapping_as_supplemental_texts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->createRequiresBookMappingFixture();

        $path = base_path('.tmp/legacy-supplemental-command-test.sql');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO `verse` (`verseID`, `bookID`, `bibleID`, `chapterID`, `verseNr`, `vers`, `description`, `history`) VALUES
(98502, 196, 3, 3681, 1, '1 Павло, апостол Христа Ісуса, з волі Бога.', '', '0000-00-00 00:00:00');
SQL);

        try {
            $this->artisan('bible:legacy:import-supplemental-texts', [
                '--path' => '.tmp/legacy-supplemental-command-test.sql',
                '--types' => 'requires_book_mapping',
            ])
                ->expectsOutputToContain('Imported supplemental legacy texts: 1 rows.')
                ->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('legacy_supplemental_texts', [
            'legacy_verse_id' => 98502,
            'legacy_bible_id' => 3,
            'legacy_book_id' => 196,
            'legacy_chapter_id' => 3681,
            'legacy_book_slug' => '2thessalonians',
            'legacy_chapter_number' => 4,
            'legacy_verse_number' => 1,
            'type' => 'requires_book_mapping',
            'title' => '-е Тимофiю',
            'text' => 'Павло, апостол Христа Ісуса, з волі Бога.',
        ]);
    }

    private function createLegacyHeadingFixture(): void
    {
        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $matthewId = DB::table('canonical_books')->where('slug', 'matthew')->value('id');

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

        DB::table('legacy_libraries')->insert([
            'legacy_id' => 492,
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_books')->insert([
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'canonical_book_id' => $matthewId,
            'legacy_book_id' => 3978,
            'slug' => 'matthew',
            'name' => 'От Матфея',
            'short_name' => 'Матф.',
            'book_order' => 1,
            'chapters_count' => 29,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleBookId = DB::table('module_books')->where('legacy_book_id', 3978)->value('id');

        DB::table('legacy_books')->insert([
            'legacy_id' => 3978,
            'legacy_bible_id' => 492,
            'module_book_id' => $moduleBookId,
            'canonical_book_id' => $matthewId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_chapters')->insert([
            'module_book_id' => $moduleBookId,
            'canonical_chapter_id' => null,
            'legacy_chapter_id' => 231597,
            'chapter_number' => 0,
            'title' => 'Intro',
            'verses_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleChapterId = DB::table('module_chapters')->where('legacy_chapter_id', 231597)->value('id');

        DB::table('legacy_chapters')->insert([
            'legacy_id' => 231597,
            'legacy_book_id' => 3978,
            'legacy_bible_id' => 492,
            'module_chapter_id' => $moduleChapterId,
            'canonical_chapter_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('legacy_canonical_chapter_overrides')->insert([
            'legacy_bible_id' => 492,
            'legacy_book_slug' => 'matthew',
            'legacy_chapter_number' => 0,
            'action' => 'heading',
            'reason' => 'ibsb_nt_book_intro',
            'note' => 'Test heading.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createRequiresBookMappingFixture(): void
    {
        $now = now();
        $languageId = DB::table('languages')->where('code', 'uk')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $bookId = DB::table('canonical_books')->where('slug', '2thessalonians')->value('id');

        DB::table('modules')->insert([
            'language_id' => $languageId,
            'type' => 'bible',
            'code' => 'L3_UKR',
            'name' => 'UKR Test',
            'short_name' => 'UKR',
            'is_active' => true,
            'is_public' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleId = DB::table('modules')->where('code', 'L3_UKR')->value('id');

        DB::table('translations')->insert([
            'module_id' => $moduleId,
            'language_id' => $languageId,
            'canon_id' => $canonId,
            'code' => 'L3_UKR',
            'name' => 'UKR Test',
            'short_name' => 'UKR',
            'has_new_testament' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $translationId = DB::table('translations')->where('code', 'L3_UKR')->value('id');

        DB::table('legacy_libraries')->insert([
            'legacy_id' => 3,
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_books')->insert([
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'canonical_book_id' => $bookId,
            'legacy_book_id' => 196,
            'slug' => '2thessalonians',
            'name' => '2-е до солунян',
            'short_name' => '2Сол.',
            'book_order' => 53,
            'chapters_count' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleBookId = DB::table('module_books')->where('legacy_book_id', 196)->value('id');

        DB::table('legacy_books')->insert([
            'legacy_id' => 196,
            'legacy_bible_id' => 3,
            'module_book_id' => $moduleBookId,
            'canonical_book_id' => $bookId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('module_chapters')->insert([
            'module_book_id' => $moduleBookId,
            'canonical_chapter_id' => null,
            'legacy_chapter_id' => 3681,
            'chapter_number' => 4,
            'title' => '-е Тимофiю',
            'verses_count' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleChapterId = DB::table('module_chapters')->where('legacy_chapter_id', 3681)->value('id');

        DB::table('legacy_chapters')->insert([
            'legacy_id' => 3681,
            'legacy_book_id' => 196,
            'legacy_bible_id' => 3,
            'module_chapter_id' => $moduleChapterId,
            'canonical_chapter_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('legacy_canonical_chapter_overrides')->insert([
            'legacy_bible_id' => 3,
            'legacy_book_slug' => '2thessalonians',
            'legacy_chapter_number' => 4,
            'action' => 'requires_book_mapping',
            'target_book_slug' => '1timothy',
            'target_chapter_number' => 1,
            'reason' => 'legacy_ukr_misaligned_chapter',
            'note' => 'Preserved as supplemental duplicate instead of overwriting canonical 1 Timothy 1.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
