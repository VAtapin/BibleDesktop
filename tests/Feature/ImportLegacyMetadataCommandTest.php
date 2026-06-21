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

class ImportLegacyMetadataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_import_classifies_lop_as_commentary_not_translation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $path = base_path('.tmp/legacy-metadata-command-test.sql');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $this->legacySqlFixture());

        try {
            $this->artisan('bible:legacy:import-metadata', [
                '--path' => '.tmp/legacy-metadata-command-test.sql',
            ])->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('modules', [
            'code' => 'L1_RST',
            'type' => 'bible',
        ]);
        $this->assertDatabaseHas('translations', [
            'code' => 'L1_RST',
        ]);
        $this->assertDatabaseHas('module_books', [
            'legacy_book_id' => 1,
            'slug' => 'genesis',
        ]);

        $this->assertDatabaseHas('modules', [
            'code' => 'L376_LOP',
            'type' => 'commentary',
        ]);
        $this->assertDatabaseMissing('translations', [
            'code' => 'L376_LOP',
        ]);
        $this->assertDatabaseMissing('module_books', [
            'legacy_book_id' => 3700,
        ]);

        $legacyLibrary = DB::table('legacy_libraries')->where('legacy_id', 376)->first();

        $this->assertNotNull($legacyLibrary);
        $this->assertNotNull($legacyLibrary->module_id);
        $this->assertNull($legacyLibrary->translation_id);
    }

    public function test_metadata_import_applies_chapter_mapping_overrides(): void
    {
        $this->seed(DatabaseSeeder::class);

        DB::table('legacy_canonical_chapter_overrides')->insert([
            'legacy_bible_id' => 1,
            'legacy_book_slug' => 'genesis',
            'legacy_chapter_number' => 51,
            'action' => 'map_chapter',
            'target_book_slug' => 'genesis',
            'target_chapter_number' => 50,
            'reason' => 'test override',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = base_path('.tmp/legacy-metadata-command-test.sql');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $this->legacySqlFixture());

        try {
            $this->artisan('bible:legacy:import-metadata', [
                '--path' => '.tmp/legacy-metadata-command-test.sql',
            ])->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $canonicalBookId = DB::table('canonical_books')->where('slug', 'genesis')->value('id');
        $canonicalChapterId = DB::table('canonical_chapters')
            ->where('canonical_book_id', $canonicalBookId)
            ->where('number', 50)
            ->value('id');

        $this->assertDatabaseHas('legacy_chapters', [
            'legacy_id' => 100,
            'canonical_chapter_id' => $canonicalChapterId,
        ]);
    }

    private function legacySqlFixture(): string
    {
        return <<<'SQL'
INSERT INTO `library` (`id`, `bibleName`, `bibleShortName`, `bible`, `oldTestament`, `newTestament`, `apocrypha`, `strongNumbers`, `language`, `path`, `bookQty`, `htmlFilter`, `chapterSign`, `verseSign`, `status`, `published`, `order`, `description`) VALUES
(1, 'Russian Synodal', 'RST', 'Y', 'Y', 'Y', 'Y', 'Y', 'Русский', 'rst/', 77, '<p></p>', 'Глава ', '', 1, 1, 1, ''),
(376, 'Толковая Библии - А.Лопухина', 'LOP', 'Y', 'Y', 'Y', 'Y', 'N', 'Русский', 'Lop/', 204, '<p></p>', 'Глава ', '', 1, 1, 4, '');
INSERT INTO `book` (`bookID`, `bibleID`, `bookIndex`, `pathName`, `fullName`, `shortName`, `chapterQty`, `lexiconID`, `description`, `versNrView`) VALUES
(1, 1, 'genesis', 'ru01.htm', 'Бытие', 'Быт.', 50, 0, '1', 1),
(3700, 376, 'genesis', 'Genesis.htm', 'Бытие', 'Быт.', 51, 0, '1', 1);
INSERT INTO `chapter` (`chapterID`, `bookID`, `bibleID`, `chapterNr`, `verseQty`, `title`, `description`, `dataTime`, `admin`) VALUES
(1, 1, 1, 1, 31, 'Глава ', '', '0000-00-00 00:00:00', 0),
(100, 1, 1, 51, 2, 'Глава ', '', '0000-00-00 00:00:00', 0),
(227800, 3700, 376, 1, 31, 'Глава ', '', '0000-00-00 00:00:00', 0);
SQL;
    }
}
