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

namespace App\Console\Commands;

use App\Support\LegacySqlDump;
use App\Support\StrongText;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportLegacyVerses extends Command
{
    protected $signature = 'bible:legacy:import-verses
        {--path=OLD/bible-desktop.sql}
        {--library=1 : Legacy library id to import}
        {--all : Import all legacy libraries that have metadata mappings}
        {--missing-only : Skip legacy verses that already have mappings}
        {--chunk=500 : Database upsert chunk size}';

    protected $description = 'Import legacy verses for one translation into verses, verse_texts, and legacy_verses.';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));
        $libraryId = (int) $this->option('library');
        $chunkSize = max(100, (int) $this->option('chunk'));

        if (DB::connection()->getDriverName() === 'sqlite') {
            $chunkSize = min($chunkSize, 500);
        }

        if (! is_file($path)) {
            $this->error("Legacy SQL dump not found: {$path}");

            return self::FAILURE;
        }

        if ((bool) $this->option('all')) {
            return $this->importAllLibraries($path, $chunkSize, (bool) $this->option('missing-only'));
        }

        $legacyLibrary = DB::table('legacy_libraries')->where('legacy_id', $libraryId)->first();

        if (! $legacyLibrary || ! $legacyLibrary->translation_id) {
            $this->error("Legacy library {$libraryId} is not imported. Run bible:legacy:import-metadata first.");

            return self::FAILURE;
        }

        $bookMap = DB::table('legacy_books')
            ->join('module_books', 'module_books.id', '=', 'legacy_books.module_book_id')
            ->where('legacy_bible_id', $libraryId)
            ->get([
                'legacy_books.legacy_id',
                'legacy_books.module_book_id',
                'legacy_books.canonical_book_id',
                'module_books.slug',
            ])
            ->keyBy('legacy_id');
        $chapterMap = DB::table('legacy_chapters')
            ->join('module_chapters', 'module_chapters.id', '=', 'legacy_chapters.module_chapter_id')
            ->leftJoin('canonical_chapters', 'canonical_chapters.id', '=', 'legacy_chapters.canonical_chapter_id')
            ->where('legacy_bible_id', $libraryId)
            ->get([
                'legacy_chapters.legacy_id',
                'legacy_chapters.module_chapter_id',
                'legacy_chapters.canonical_chapter_id',
                'module_chapters.chapter_number',
                'canonical_chapters.canonical_book_id as canonical_chapter_book_id',
                'canonical_chapters.number as canonical_chapter_number',
            ])
            ->keyBy('legacy_id');

        if ($bookMap->isEmpty() || $chapterMap->isEmpty()) {
            $this->error("Legacy metadata for library {$libraryId} is missing. Run bible:legacy:import-metadata first.");

            return self::FAILURE;
        }

        $osisCodes = DB::table('canonical_books')->pluck('osis_code', 'id')->all();
        $verseOverrides = $this->verseOverrides();

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
            $sourceRow = $this->legacyVerseSourceRow($row, $libraryId, $bookMap, $chapterMap, $osisCodes, $verseOverrides);

            if (! $sourceRow) {
                $skipped++;

                continue;
            }

            $sourceRows[] = $sourceRow;

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

    private function importAllLibraries(string $path, int $chunkSize, bool $missingOnly): int
    {
        $libraries = DB::table('legacy_libraries')
            ->whereNotNull('translation_id')
            ->pluck('translation_id', 'legacy_id')
            ->all();

        if ($libraries === []) {
            $this->error('Legacy libraries are missing. Run bible:legacy:import-metadata first.');

            return self::FAILURE;
        }

        $bookMap = DB::table('legacy_books')
            ->join('module_books', 'module_books.id', '=', 'legacy_books.module_book_id')
            ->get([
                'legacy_books.legacy_id',
                'legacy_books.legacy_bible_id',
                'legacy_books.module_book_id',
                'legacy_books.canonical_book_id',
                'module_books.slug',
            ])
            ->keyBy('legacy_id');
        $chapterMap = DB::table('legacy_chapters')
            ->join('module_chapters', 'module_chapters.id', '=', 'legacy_chapters.module_chapter_id')
            ->leftJoin('canonical_chapters', 'canonical_chapters.id', '=', 'legacy_chapters.canonical_chapter_id')
            ->get([
                'legacy_chapters.legacy_id',
                'legacy_chapters.legacy_bible_id',
                'legacy_chapters.module_chapter_id',
                'legacy_chapters.canonical_chapter_id',
                'module_chapters.chapter_number',
                'canonical_chapters.canonical_book_id as canonical_chapter_book_id',
                'canonical_chapters.number as canonical_chapter_number',
            ])
            ->keyBy('legacy_id');

        if ($bookMap->isEmpty() || $chapterMap->isEmpty()) {
            $this->error('Legacy metadata is missing. Run bible:legacy:import-metadata first.');

            return self::FAILURE;
        }

        $osisCodes = DB::table('canonical_books')->pluck('osis_code', 'id')->all();
        $verseOverrides = $this->verseOverrides();
        $reader = new LegacySqlDump($path);
        $sourceRowsByLibrary = [];
        $imported = 0;
        $skipped = 0;
        $alreadyImported = 0;

        foreach ($reader->rows('verse') as $row) {
            $libraryId = (int) $row['bibleID'];
            $translationId = $libraries[$libraryId] ?? null;

            if (! $translationId) {
                continue;
            }

            $sourceRow = $this->legacyVerseSourceRow($row, $libraryId, $bookMap, $chapterMap, $osisCodes, $verseOverrides);

            if (! $sourceRow) {
                $skipped++;

                continue;
            }

            $sourceRowsByLibrary[$libraryId] ??= [];
            $sourceRowsByLibrary[$libraryId][] = $sourceRow;

            if (count($sourceRowsByLibrary[$libraryId]) >= $chunkSize) {
                $result = $this->importVerseChunkSkippingExisting($sourceRowsByLibrary[$libraryId], (int) $translationId, $chunkSize, $missingOnly);
                $imported += $result['imported'];
                $skipped += $result['skipped'];
                $alreadyImported += $result['already_imported'];
                $sourceRowsByLibrary[$libraryId] = [];
            }
        }

        foreach ($sourceRowsByLibrary as $libraryId => $sourceRows) {
            if ($sourceRows === []) {
                continue;
            }

            $result = $this->importVerseChunkSkippingExisting($sourceRows, (int) $libraries[$libraryId], $chunkSize, $missingOnly);
            $imported += $result['imported'];
            $skipped += $result['skipped'];
            $alreadyImported += $result['already_imported'];
        }

        $this->components->info(sprintf(
            'Imported verses for %d legacy libraries: %d verse texts, %d skipped, %d already imported.',
            count($libraries),
            $imported,
            $skipped,
            $alreadyImported,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $sourceRows
     * @return array{imported: int, skipped: int, already_imported: int}
     */
    private function importVerseChunkSkippingExisting(array $sourceRows, int $translationId, int $chunkSize, bool $missingOnly): array
    {
        $alreadyImported = 0;

        if ($missingOnly) {
            $legacyIds = array_column($sourceRows, 'legacy_id');
            $existing = DB::table('legacy_verses')
                ->whereIn('legacy_id', $legacyIds)
                ->pluck('legacy_id')
                ->all();
            $existing = array_fill_keys($existing, true);
            $alreadyImported = count($existing);
            $sourceRows = array_values(array_filter(
                $sourceRows,
                fn (array $row): bool => ! isset($existing[$row['legacy_id']]),
            ));
        }

        if ($sourceRows === []) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'already_imported' => $alreadyImported,
            ];
        }

        $result = $this->importVerseChunk($sourceRows, $translationId, $chunkSize);

        return [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'already_imported' => $alreadyImported,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sourceRows
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

            $text = StrongText::cleanModuleText((string) $row['raw_text']);

            $verseTextRows[] = [
                'verse_id' => $verseId,
                'translation_id' => $translationId,
                'module_book_id' => $row['module_book_id'],
                'module_chapter_id' => $row['module_chapter_id'],
                'legacy_verse_id' => $row['legacy_id'],
                'text' => $text,
                'has_strong_markup' => StrongText::hasStrongNumbers($text),
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
            ['module_book_id', 'module_chapter_id', 'legacy_verse_id', 'text', 'has_strong_markup', 'metadata_json', 'updated_at'],
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
     * @param  array<string, mixed>  $row
     * @param  Collection<int, object>  $bookMap
     * @param  Collection<int, object>  $chapterMap
     * @param  array<int, string|null>  $osisCodes
     * @param  array<string, array{canonical_book_id: int, canonical_chapter_id: int, chapter_number: int, verse_number: int, osis_code: string|null}>  $verseOverrides
     * @return array<string, mixed>|null
     */
    private function legacyVerseSourceRow(array $row, int $libraryId, $bookMap, $chapterMap, array $osisCodes, array $verseOverrides): ?array
    {
        $legacyBookId = (int) $row['bookID'];
        $legacyChapterId = (int) $row['chapterID'];
        $legacyVerseNumber = (int) $row['verseNr'];
        $book = $bookMap[$legacyBookId] ?? null;
        $chapter = $chapterMap[$legacyChapterId] ?? null;

        if (! $book || ! $chapter) {
            return null;
        }

        $verseOverride = $this->verseOverride(
            $verseOverrides,
            $libraryId,
            (string) $book->slug,
            (int) $chapter->chapter_number,
            $legacyVerseNumber,
        );

        if ($verseOverride) {
            $canonicalBookId = $verseOverride['canonical_book_id'];
            $canonicalChapterId = $verseOverride['canonical_chapter_id'];
            $chapterNumber = $verseOverride['chapter_number'];
            $verseNumber = $verseOverride['verse_number'];
            $osisCode = $verseOverride['osis_code'];
        } else {
            if (! $chapter->canonical_chapter_id) {
                return null;
            }

            $canonicalBookId = (int) ($chapter->canonical_chapter_book_id ?: $book->canonical_book_id);

            if (! $canonicalBookId) {
                return null;
            }

            $canonicalChapterId = (int) $chapter->canonical_chapter_id;
            $chapterNumber = (int) ($chapter->canonical_chapter_number ?: $chapter->chapter_number);
            $verseNumber = $legacyVerseNumber;
            $osisCode = $osisCodes[$canonicalBookId] ?? null;
        }

        return [
            'legacy_id' => (int) $row['verseID'],
            'legacy_book_id' => $legacyBookId,
            'legacy_chapter_id' => $legacyChapterId,
            'legacy_bible_id' => $libraryId,
            'module_book_id' => (int) $book->module_book_id,
            'module_chapter_id' => (int) $chapter->module_chapter_id,
            'canonical_book_id' => $canonicalBookId,
            'canonical_chapter_id' => $canonicalChapterId,
            'chapter_number' => $chapterNumber,
            'verse_number' => $verseNumber,
            'osis_code' => $osisCode,
            'raw_text' => (string) $row['vers'],
            'raw_json' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @return array<string, array{canonical_book_id: int, canonical_chapter_id: int, chapter_number: int, verse_number: int, osis_code: string|null}>
     */
    private function verseOverrides(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('legacy_canonical_verse_overrides')) {
            return [];
        }

        $canonicalBooks = DB::table('canonical_books')
            ->get(['id', 'slug', 'osis_code'])
            ->mapWithKeys(fn ($book) => [
                (string) $book->slug => [
                    'id' => (int) $book->id,
                    'osis_code' => $book->osis_code === null ? null : (string) $book->osis_code,
                ],
            ])
            ->all();
        $canonicalChapters = DB::table('canonical_chapters')
            ->join('canonical_books', 'canonical_books.id', '=', 'canonical_chapters.canonical_book_id')
            ->get(['canonical_chapters.id', 'canonical_chapters.number', 'canonical_books.slug'])
            ->mapWithKeys(fn ($chapter) => ["{$chapter->slug}:{$chapter->number}" => (int) $chapter->id])
            ->all();

        return DB::table('legacy_canonical_verse_overrides')
            ->where('action', 'map_verse')
            ->get([
                'legacy_bible_id',
                'legacy_book_slug',
                'legacy_chapter_number',
                'legacy_verse_number',
                'target_book_slug',
                'target_chapter_number',
                'target_verse_number',
            ])
            ->mapWithKeys(function ($override) use ($canonicalBooks, $canonicalChapters): array {
                $targetBook = $canonicalBooks[(string) $override->target_book_slug] ?? null;
                $targetChapterNumber = (int) $override->target_chapter_number;
                $targetChapterId = $canonicalChapters["{$override->target_book_slug}:{$targetChapterNumber}"] ?? null;

                if (! $targetBook || ! $targetChapterId || ! $override->target_verse_number) {
                    return [];
                }

                $legacyBibleId = $override->legacy_bible_id === null ? '*' : (string) $override->legacy_bible_id;

                return [
                    "{$legacyBibleId}:{$override->legacy_book_slug}:{$override->legacy_chapter_number}:{$override->legacy_verse_number}" => [
                        'canonical_book_id' => $targetBook['id'],
                        'canonical_chapter_id' => $targetChapterId,
                        'chapter_number' => $targetChapterNumber,
                        'verse_number' => (int) $override->target_verse_number,
                        'osis_code' => $targetBook['osis_code'],
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, array{canonical_book_id: int, canonical_chapter_id: int, chapter_number: int, verse_number: int, osis_code: string|null}>  $verseOverrides
     * @return array{canonical_book_id: int, canonical_chapter_id: int, chapter_number: int, verse_number: int, osis_code: string|null}|null
     */
    private function verseOverride(array $verseOverrides, int $legacyBibleId, string $legacyBookSlug, int $legacyChapterNumber, int $legacyVerseNumber): ?array
    {
        return $verseOverrides["{$legacyBibleId}:{$legacyBookSlug}:{$legacyChapterNumber}:{$legacyVerseNumber}"]
            ?? $verseOverrides["*:{$legacyBookSlug}:{$legacyChapterNumber}:{$legacyVerseNumber}"]
            ?? null;
    }
}
