<?php

namespace App\Console\Commands;

use App\Support\LegacySqlDump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyVerses extends Command
{
    protected $signature = 'bible:legacy:import-verses
        {--path=OLD/bible-desktop.sql}
        {--library=1 : Legacy library id to import}
        {--chunk=500 : Database upsert chunk size}';

    protected $description = 'Import legacy verses for one translation into verses, verse_texts, and legacy_verses.';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));
        $libraryId = (int) $this->option('library');
        $chunkSize = max(100, (int) $this->option('chunk'));

        if (! is_file($path)) {
            $this->error("Legacy SQL dump not found: {$path}");

            return self::FAILURE;
        }

        $legacyLibrary = DB::table('legacy_libraries')->where('legacy_id', $libraryId)->first();

        if (! $legacyLibrary || ! $legacyLibrary->translation_id) {
            $this->error("Legacy library {$libraryId} is not imported. Run bible:legacy:import-metadata first.");

            return self::FAILURE;
        }

        $bookMap = DB::table('legacy_books')
            ->where('legacy_bible_id', $libraryId)
            ->get(['legacy_id', 'module_book_id', 'canonical_book_id'])
            ->keyBy('legacy_id');
        $chapterMap = DB::table('legacy_chapters')
            ->join('module_chapters', 'module_chapters.id', '=', 'legacy_chapters.module_chapter_id')
            ->where('legacy_bible_id', $libraryId)
            ->get([
                'legacy_chapters.legacy_id',
                'legacy_chapters.module_chapter_id',
                'legacy_chapters.canonical_chapter_id',
                'module_chapters.chapter_number',
            ])
            ->keyBy('legacy_id');

        if ($bookMap->isEmpty() || $chapterMap->isEmpty()) {
            $this->error("Legacy metadata for library {$libraryId} is missing. Run bible:legacy:import-metadata first.");

            return self::FAILURE;
        }

        $osisCodes = DB::table('canonical_books')
            ->whereIn('id', $bookMap->pluck('canonical_book_id')->filter()->unique()->values())
            ->pluck('osis_code', 'id')
            ->all();

        $reader = new LegacySqlDump($path);
        $sourceRows = [];
        $skipped = 0;
        $imported = 0;
        $seenTargetLibrary = false;

        foreach ($reader->rows('verse') as $row) {
            $rowLibraryId = (int) $row['bibleID'];

            if ($seenTargetLibrary && $rowLibraryId > $libraryId) {
                break;
            }

            if ($rowLibraryId !== $libraryId) {
                continue;
            }

            $seenTargetLibrary = true;
            $legacyBookId = (int) $row['bookID'];
            $legacyChapterId = (int) $row['chapterID'];
            $book = $bookMap[$legacyBookId] ?? null;
            $chapter = $chapterMap[$legacyChapterId] ?? null;

            if (! $book || ! $chapter || ! $book->canonical_book_id || ! $chapter->canonical_chapter_id) {
                $skipped++;
                continue;
            }

            $chapterNumber = (int) $chapter->chapter_number;
            $verseNumber = (int) $row['verseNr'];
            $osisCode = $osisCodes[$book->canonical_book_id] ?? null;

            $sourceRows[] = [
                'legacy_id' => (int) $row['verseID'],
                'legacy_book_id' => $legacyBookId,
                'legacy_chapter_id' => $legacyChapterId,
                'legacy_bible_id' => $libraryId,
                'module_book_id' => (int) $book->module_book_id,
                'module_chapter_id' => (int) $chapter->module_chapter_id,
                'canonical_book_id' => (int) $book->canonical_book_id,
                'canonical_chapter_id' => (int) $chapter->canonical_chapter_id,
                'chapter_number' => $chapterNumber,
                'verse_number' => $verseNumber,
                'osis_code' => $osisCode,
                'raw_text' => (string) $row['vers'],
                'raw_json' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ];

            if (count($sourceRows) >= $chunkSize) {
                $result = $this->importVerseChunk($sourceRows, (int) $legacyLibrary->translation_id, $chunkSize);
                $imported += $result['imported'];
                $skipped += $result['skipped'];
                $sourceRows = [];
            }
        }

        if ($sourceRows !== []) {
            $result = $this->importVerseChunk($sourceRows, (int) $legacyLibrary->translation_id, $chunkSize);
            $imported += $result['imported'];
            $skipped += $result['skipped'];
        }

        $this->components->info(sprintf(
            'Imported verses for legacy library %d: %d verse texts, %d skipped.',
            $libraryId,
            $imported,
            $skipped,
        ));

        return self::SUCCESS;
    }

    /**
     * @param list<array<string, mixed>> $sourceRows
     * @return array{imported: int, skipped: int}
     */
    private function importVerseChunk(array $sourceRows, int $translationId, int $chunkSize): array
    {
        $now = now();
        $skipped = 0;
        $verseRows = [];

        foreach ($sourceRows as $row) {
            $verseRows[] = [
                'canonical_book_id' => $row['canonical_book_id'],
                'canonical_chapter_id' => $row['canonical_chapter_id'],
                'chapter_number' => $row['chapter_number'],
                'verse_number' => $row['verse_number'],
                'osis_ref' => $row['osis_code'] ? "{$row['osis_code']}.{$row['chapter_number']}.{$row['verse_number']}" : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('verses')->upsert(
            $verseRows,
            ['canonical_book_id', 'chapter_number', 'verse_number'],
            ['canonical_chapter_id', 'osis_ref', 'updated_at'],
        );

        $verseIds = DB::table('verses')
            ->whereIn('canonical_book_id', array_values(array_unique(array_column($verseRows, 'canonical_book_id'))))
            ->get(['id', 'canonical_book_id', 'chapter_number', 'verse_number'])
            ->mapWithKeys(fn ($verse) => ["{$verse->canonical_book_id}:{$verse->chapter_number}:{$verse->verse_number}" => (int) $verse->id])
            ->all();
        $verseTextRows = [];
        $legacyVerseSourceRows = [];

        foreach ($sourceRows as $row) {
            $verseId = $verseIds["{$row['canonical_book_id']}:{$row['chapter_number']}:{$row['verse_number']}"] ?? null;

            if (! $verseId) {
                $skipped++;
                continue;
            }

            $normalized = $this->normalizeVerseText((string) $row['raw_text']);

            $verseTextRows[] = [
                'verse_id' => $verseId,
                'translation_id' => $translationId,
                'module_book_id' => $row['module_book_id'],
                'module_chapter_id' => $row['module_chapter_id'],
                'legacy_verse_id' => $row['legacy_id'],
                'text' => $normalized['text'],
                'text_plain' => $normalized['plain'],
                'text_raw' => $row['raw_text'],
                'has_strong_markup' => $normalized['has_strong'],
                'metadata_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $legacyVerseSourceRows[] = [
                'legacy_id' => $row['legacy_id'],
                'legacy_book_id' => $row['legacy_book_id'],
                'legacy_chapter_id' => $row['legacy_chapter_id'],
                'legacy_bible_id' => $row['legacy_bible_id'],
                'verse_id' => $verseId,
                'raw_json' => $row['raw_json'],
            ];
        }

        DB::table('verse_texts')->upsert(
            $verseTextRows,
            ['translation_id', 'verse_id'],
            ['module_book_id', 'module_chapter_id', 'legacy_verse_id', 'text', 'text_plain', 'text_raw', 'has_strong_markup', 'metadata_json', 'updated_at'],
        );

        $verseTextIds = DB::table('verse_texts')
            ->where('translation_id', $translationId)
            ->whereIn('verse_id', array_values(array_unique(array_column($legacyVerseSourceRows, 'verse_id'))))
            ->get(['id', 'verse_id'])
            ->pluck('id', 'verse_id')
            ->all();
        $legacyVerseRows = [];

        foreach ($legacyVerseSourceRows as $row) {
            $verseTextId = $verseTextIds[$row['verse_id']] ?? null;

            if (! $verseTextId) {
                $skipped++;
                continue;
            }

            $legacyVerseRows[] = [
                'legacy_id' => $row['legacy_id'],
                'legacy_book_id' => $row['legacy_book_id'],
                'legacy_chapter_id' => $row['legacy_chapter_id'],
                'legacy_bible_id' => $row['legacy_bible_id'],
                'verse_id' => $row['verse_id'],
                'verse_text_id' => $verseTextId,
                'raw_json' => $row['raw_json'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($legacyVerseRows, $chunkSize) as $chunk) {
            DB::table('legacy_verses')->upsert(
                $chunk,
                ['legacy_id'],
                ['legacy_book_id', 'legacy_chapter_id', 'legacy_bible_id', 'verse_id', 'verse_text_id', 'raw_json', 'updated_at'],
            );
        }

        return [
            'imported' => count($verseTextRows),
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{text: string, plain: string, has_strong: bool}
     */
    private function normalizeVerseText(string $rawText): array
    {
        $text = preg_replace('/^\s*\d+\s*/u', '', $rawText) ?? $rawText;
        $hasStrong = preg_match('/\b[HG]\d{3,5}\b/u', $text) === 1;
        $text = preg_replace('/\s*\b[HG]\d{3,5}\b/u', '', $text) ?? $text;
        $text = preg_replace('/\s+([,.;:!?])/u', '$1', $text) ?? $text;
        $text = preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s{2,}/u', ' ', trim($plain)) ?? trim($plain);

        return [
            'text' => $text,
            'plain' => $plain,
            'has_strong' => $hasStrong,
        ];
    }

}
