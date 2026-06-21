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
use ZipArchive;

class ImportBibleQuoteModulesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_replaces_existing_bible_translations_and_imports_biblequote_zip(): void
    {
        $this->seed(DatabaseSeeder::class);

        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');

        DB::table('modules')->insert([
            'language_id' => $languageId,
            'type' => 'bible',
            'code' => 'OLD_RST',
            'name' => 'Old RST',
            'short_name' => 'RST',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $oldModuleId = DB::table('modules')->where('code', 'OLD_RST')->value('id');
        DB::table('translations')->insert([
            'module_id' => $oldModuleId,
            'language_id' => $languageId,
            'canon_id' => $canonId,
            'code' => 'OLD_RST',
            'name' => 'Old RST',
            'short_name' => 'RST',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $path = base_path('.tmp/Bible_Russian_RST.zip');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('Bible_Russian_RST/bibleqt.ini', <<<'INI'
BibleName=Тестовый Синодальный
BibleShortName=RST
Bible=Y
OldTestament=Y
NewTestament=N
Apocrypha=N
StrongNumbers=Y
DefaultEncoding=utf-8
BookQty=1
PathName=01_genesis.htm
FullName=Бытие
ShortName=Быт. Быт Genesis
ChapterQty=1
INI);
        $zip->addFromString('Bible_Russian_RST/01_genesis.htm', <<<'HTML'
<h4>1</h4>
<p><sup>1</sup> В G1722 начале G746 сотворил Бог небо и землю.
<p><sup>2</sup> <font COLOR="darkred">Земля же была безвидна</font> и пуста.
HTML);
        $zip->close();

        try {
            $this->artisan('bible:bq:import', [
                '--path' => '.tmp/Bible_Russian_RST.zip',
                '--replace' => true,
            ])->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseMissing('translations', ['code' => 'OLD_RST']);
        $this->assertDatabaseHas('translations', [
            'code' => 'BQ_RUSSIAN_RST',
            'name' => 'Тестовый Синодальный',
            'short_name' => 'RST',
            'has_strong' => true,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('module_books', [
            'slug' => 'genesis',
            'name' => 'Бытие',
            'chapters_count' => 1,
        ]);
        $this->assertDatabaseHas('verse_texts', [
            'text' => 'В G1722 начале G746 сотворил Бог небо и землю.',
            'has_strong_markup' => true,
        ]);
        $this->assertDatabaseHas('verse_texts', [
            'text' => '<font color="darkred">Земля же была безвидна</font> и пуста.',
            'has_strong_markup' => false,
        ]);
    }

    public function test_it_imports_mybible_sqlite_modules_with_inline_strong_tags(): void
    {
        $this->seed(DatabaseSeeder::class);

        $path = base_path('.tmp/NASB_TEST.SQLite3');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        @unlink($path);

        $pdo = new \PDO('sqlite:'.$path);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE info (name TEXT, value TEXT)');
        $pdo->exec('CREATE TABLE books (book_color TEXT, book_number INTEGER, short_name TEXT, long_name TEXT)');
        $pdo->exec('CREATE TABLE verses (book_number NUMERIC, chapter NUMERIC, verse NUMERIC, text TEXT)');
        $pdo->exec("INSERT INTO info (name, value) VALUES ('description', 'NASB Test'), ('language', 'en'), ('strong_numbers', 'true')");
        $pdo->exec("INSERT INTO books (book_color, book_number, short_name, long_name) VALUES ('', 10, 'Gen', 'Genesis')");
        $statement = $pdo->prepare('INSERT INTO verses (book_number, chapter, verse, text) VALUES (?, ?, ?, ?)');
        $statement->execute([10, 1, 1, '<pb/>In the beginning<S>7225</S> God<S>430</S> created<S>1254</S>.']);

        try {
            $this->artisan('bible:bq:import', [
                '--path' => $path,
                '--languages' => 'en',
                '--replace' => true,
            ])->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('translations', [
            'code' => 'BQ_NASB_TEST',
            'name' => 'NASB Test',
            'has_strong' => true,
        ]);
        $this->assertDatabaseHas('verse_texts', [
            'text' => 'In the beginning 7225 God 430 created 1254.',
            'has_strong_markup' => true,
        ]);
    }
}
