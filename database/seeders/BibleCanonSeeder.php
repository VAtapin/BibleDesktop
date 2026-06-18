<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BibleCanonSeeder extends Seeder
{
    /**
     * Book order follows legacy RST library id 1, which contains 77 books.
     */
    private const BOOKS = [
        ['genesis', 'Gen', 'old', 'Genesis', 50, false],
        ['exodus', 'Exod', 'old', 'Exodus', 40, false],
        ['leviticus', 'Lev', 'old', 'Leviticus', 27, false],
        ['numbers', 'Num', 'old', 'Numbers', 36, false],
        ['deuteronomy', 'Deut', 'old', 'Deuteronomy', 34, false],
        ['joshua', 'Josh', 'old', 'Joshua', 24, false],
        ['judges', 'Judg', 'old', 'Judges', 21, false],
        ['ruth', 'Ruth', 'old', 'Ruth', 4, false],
        ['1samuel', '1Sam', 'old', '1 Samuel', 31, false],
        ['2samuel', '2Sam', 'old', '2 Samuel', 24, false],
        ['1kings', '1Kgs', 'old', '1 Kings', 22, false],
        ['2kings', '2Kgs', 'old', '2 Kings', 25, false],
        ['1chron', '1Chr', 'old', '1 Chronicles', 29, false],
        ['2chron', '2Chr', 'old', '2 Chronicles', 36, false],
        ['ezra', 'Ezra', 'old', 'Ezra', 10, false],
        ['nehemiah', 'Neh', 'old', 'Nehemiah', 13, false],
        ['esther', 'Esth', 'old', 'Esther', 10, false],
        ['job', 'Job', 'old', 'Job', 42, false],
        ['psalms', 'Ps', 'old', 'Psalms', 150, false],
        ['proverbs', 'Prov', 'old', 'Proverbs', 31, false],
        ['ecclesia', 'Eccl', 'old', 'Ecclesiastes', 12, false],
        ['songs', 'Song', 'old', 'Song of Songs', 8, false],
        ['isaiah', 'Isa', 'old', 'Isaiah', 66, false],
        ['jeremiah', 'Jer', 'old', 'Jeremiah', 52, false],
        ['lamentations', 'Lam', 'old', 'Lamentations', 5, false],
        ['ezekiel', 'Ezek', 'old', 'Ezekiel', 48, false],
        ['daniel', 'Dan', 'old', 'Daniel', 14, false],
        ['hosea', 'Hos', 'old', 'Hosea', 14, false],
        ['joel', 'Joel', 'old', 'Joel', 3, false],
        ['amos', 'Amos', 'old', 'Amos', 9, false],
        ['obadiah', 'Obad', 'old', 'Obadiah', 1, false],
        ['jonah', 'Jonah', 'old', 'Jonah', 4, false],
        ['micah', 'Mic', 'old', 'Micah', 7, false],
        ['nahum', 'Nah', 'old', 'Nahum', 3, false],
        ['habakkuk', 'Hab', 'old', 'Habakkuk', 3, false],
        ['zephaniah', 'Zeph', 'old', 'Zephaniah', 3, false],
        ['haggai', 'Hag', 'old', 'Haggai', 2, false],
        ['zechariah', 'Zech', 'old', 'Zechariah', 14, false],
        ['malachi', 'Mal', 'old', 'Malachi', 4, false],
        ['matthew', 'Matt', 'new', 'Matthew', 28, false],
        ['mark', 'Mark', 'new', 'Mark', 16, false],
        ['luke', 'Luke', 'new', 'Luke', 24, false],
        ['john', 'John', 'new', 'John', 21, false],
        ['acts', 'Acts', 'new', 'Acts', 28, false],
        ['james', 'Jas', 'new', 'James', 5, false],
        ['1peter', '1Pet', 'new', '1 Peter', 5, false],
        ['2peter', '2Pet', 'new', '2 Peter', 3, false],
        ['1john', '1John', 'new', '1 John', 5, false],
        ['2john', '2John', 'new', '2 John', 1, false],
        ['3john', '3John', 'new', '3 John', 1, false],
        ['jude', 'Jude', 'new', 'Jude', 1, false],
        ['romans', 'Rom', 'new', 'Romans', 16, false],
        ['1corinthians', '1Cor', 'new', '1 Corinthians', 16, false],
        ['2corinthians', '2Cor', 'new', '2 Corinthians', 13, false],
        ['galatians', 'Gal', 'new', 'Galatians', 6, false],
        ['ephesians', 'Eph', 'new', 'Ephesians', 6, false],
        ['philippians', 'Phil', 'new', 'Philippians', 4, false],
        ['colossians', 'Col', 'new', 'Colossians', 4, false],
        ['1thessalonians', '1Thess', 'new', '1 Thessalonians', 5, false],
        ['2thessalonians', '2Thess', 'new', '2 Thessalonians', 3, false],
        ['1timothy', '1Tim', 'new', '1 Timothy', 6, false],
        ['2timothy', '2Tim', 'new', '2 Timothy', 4, false],
        ['titus', 'Titus', 'new', 'Titus', 3, false],
        ['philemon', 'Phlm', 'new', 'Philemon', 1, false],
        ['hebrews', 'Heb', 'new', 'Hebrews', 13, false],
        ['revelation', 'Rev', 'new', 'Revelation', 22, false],
        ['1maccabees', '1Macc', 'apocrypha', '1 Maccabees', 16, true],
        ['2maccabees', '2Macc', 'apocrypha', '2 Maccabees', 15, true],
        ['3maccabees', '3Macc', 'apocrypha', '3 Maccabees', 7, true],
        ['baruch', 'Bar', 'apocrypha', 'Baruch', 5, true],
        ['2esdras', '2Esd', 'apocrypha', '2 Esdras', 9, true],
        ['3esdras', '3Esd', 'apocrypha', '3 Esdras', 16, true],
        ['judith', 'Jdt', 'apocrypha', 'Judith', 16, true],
        ['epistle', 'EpJer', 'apocrypha', 'Epistle of Jeremiah', 1, true],
        ['wisdom', 'Wis', 'apocrypha', 'Wisdom of Solomon', 19, true],
        ['sirach', 'Sir', 'apocrypha', 'Sirach', 51, true],
        ['tobit', 'Tob', 'apocrypha', 'Tobit', 14, true],
    ];

    public function run(): void
    {
        $now = now();

        DB::table('canons')->updateOrInsert(
            ['code' => 'orthodox'],
            [
                'name' => 'Orthodox canon',
                'description' => 'Base 77-book canon derived from the legacy RST module ordering.',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $englishId = DB::table('languages')->where('code', 'en')->value('id');
        $bookRows = [];
        $nameRows = [];
        $chapterRows = [];

        foreach (self::BOOKS as $index => [$slug, $osisCode, $testament, $name, $chapters, $isDeuterocanonical]) {
            $order = $index + 1;

            $bookRows[] = [
                'canon_id' => $canonId,
                'slug' => $slug,
                'osis_code' => $osisCode,
                'testament' => $testament,
                'canonical_order' => $order,
                'default_chapters_count' => $chapters,
                'is_deuterocanonical' => $isDeuterocanonical,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('canonical_books')->upsert(
            $bookRows,
            ['canon_id', 'slug'],
            ['osis_code', 'testament', 'canonical_order', 'default_chapters_count', 'is_deuterocanonical', 'updated_at'],
        );

        $bookIds = DB::table('canonical_books')
            ->where('canon_id', $canonId)
            ->pluck('id', 'slug')
            ->all();

        foreach (self::BOOKS as [$slug, $osisCode, , $name, $chapters]) {
            $bookId = $bookIds[$slug];

            $nameRows[] = [
                'canonical_book_id' => $bookId,
                'language_id' => $englishId,
                'name' => $name,
                'short_name' => $name,
                'aliases_json' => json_encode([$osisCode], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            for ($chapter = 1; $chapter <= $chapters; $chapter++) {
                $chapterRows[] = [
                    'canonical_book_id' => $bookId,
                    'number' => $chapter,
                    'verses_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('canonical_book_names')->upsert(
            $nameRows,
            ['canonical_book_id', 'language_id'],
            ['name', 'short_name', 'aliases_json', 'updated_at'],
        );

        foreach (array_chunk($chapterRows, 500) as $chunk) {
            DB::table('canonical_chapters')->upsert(
                $chunk,
                ['canonical_book_id', 'number'],
                ['updated_at'],
            );
        }
    }
}
