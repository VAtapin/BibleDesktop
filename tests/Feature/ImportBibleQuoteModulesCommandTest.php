<?php

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

        $zip = new ZipArchive();
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
<p><sup>2</sup> Земля же была безвидна и пуста.
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
            'text_plain' => 'В начале сотворил Бог небо и землю.',
            'has_strong_markup' => true,
        ]);
        $this->assertDatabaseHas('verse_strong_tokens', [
            'strong_number' => 'G1722',
            'surface_text' => 'В',
        ]);
        $this->assertDatabaseHas('verse_strong_tokens', [
            'strong_number' => 'G746',
            'surface_text' => 'начале',
        ]);
    }
}
