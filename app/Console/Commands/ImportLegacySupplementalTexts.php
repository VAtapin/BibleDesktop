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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacySupplementalTexts extends Command
{
    protected $signature = 'bible:legacy:import-supplemental-texts
        {--path=OLD/bible-desktop.sql}
        {--types=heading,appendix,non_canonical,requires_book_mapping : Comma-separated override actions to import}
        {--chunk=500 : Database upsert chunk size}';

    protected $description = 'Import legacy appendix/heading/non-canonical/duplicate mapping texts into a supplemental table.';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));
        $chunkSize = max(100, (int) $this->option('chunk'));

        if (DB::connection()->getDriverName() === 'sqlite') {
            $chunkSize = min($chunkSize, 500);
        }

        if (! is_file($path)) {
            $this->error("Legacy SQL dump not found: {$path}");

            return self::FAILURE;
        }

        if (! DB::getSchemaBuilder()->hasTable('legacy_supplemental_texts')) {
            $this->error('Table legacy_supplemental_texts is missing. Run migrations first.');

            return self::FAILURE;
        }

        $types = $this->types();
        $libraries = DB::table('legacy_libraries')
            ->whereNotNull('translation_id')
            ->get(['legacy_id', 'module_id', 'translation_id'])
            ->mapWithKeys(fn ($library) => [
                (int) $library->legacy_id => [
                    'module_id' => (int) $library->module_id,
                    'translation_id' => (int) $library->translation_id,
                ],
            ])
            ->all();

        if ($libraries === []) {
            $this->error('Legacy libraries are missing. Run bible:legacy:import-metadata first.');

            return self::FAILURE;
        }

        $books = DB::table('legacy_books')
            ->join('module_books', 'module_books.id', '=', 'legacy_books.module_book_id')
            ->get([
                'legacy_books.legacy_id',
                'module_books.id as module_book_id',
                'module_books.slug',
            ])
            ->keyBy('legacy_id');
        $chapterOverrides = $this->chapterOverrides($types);
        $chapters = DB::table('legacy_chapters')
            ->join('module_chapters', 'module_chapters.id', '=', 'legacy_chapters.module_chapter_id')
            ->get([
                'legacy_chapters.legacy_id',
                'legacy_chapters.legacy_bible_id',
                'legacy_chapters.legacy_book_id',
                'module_chapters.id as module_chapter_id',
                'module_chapters.chapter_number',
                'module_chapters.title',
            ])
            ->filter(function ($chapter) use ($books, $chapterOverrides): bool {
                $book = $books[$chapter->legacy_book_id] ?? null;

                if (! $book) {
                    return false;
                }

                return $this->chapterOverride($chapterOverrides, (int) $chapter->legacy_bible_id, (string) $book->slug, (int) $chapter->chapter_number) !== null;
            })
            ->keyBy('legacy_id');

        if ($chapters->isEmpty()) {
            $this->components->info('No supplemental legacy chapters matched current overrides.');

            return self::SUCCESS;
        }

        $rows = [];
        $imported = 0;

        foreach ((new LegacySqlDump($path))->rows('verse') as $row) {
            $libraryId = (int) $row['bibleID'];
            $legacyChapterId = (int) $row['chapterID'];
            $library = $libraries[$libraryId] ?? null;
            $chapter = $chapters[$legacyChapterId] ?? null;

            if (! $library || ! $chapter) {
                continue;
            }

            $book = $books[(int) $row['bookID']] ?? null;

            if (! $book) {
                continue;
            }

            $override = $this->chapterOverride($chapterOverrides, $libraryId, (string) $book->slug, (int) $chapter->chapter_number);

            if (! $override) {
                continue;
            }

            $normalized = $this->normalizeText((string) $row['vers']);
            $rows[] = [
                'module_id' => $library['module_id'],
                'translation_id' => $library['translation_id'],
                'module_book_id' => (int) $book->module_book_id,
                'module_chapter_id' => (int) $chapter->module_chapter_id,
                'legacy_verse_id' => (int) $row['verseID'],
                'legacy_bible_id' => $libraryId,
                'legacy_book_id' => (int) $row['bookID'],
                'legacy_chapter_id' => $legacyChapterId,
                'legacy_book_slug' => (string) $book->slug,
                'legacy_chapter_number' => (int) $chapter->chapter_number,
                'legacy_verse_number' => (int) $row['verseNr'],
                'type' => (string) $override->action,
                'title' => $chapter->title ?: null,
                'text' => $normalized['text'],
                'text_plain' => $normalized['plain'],
                'text_raw' => (string) $row['vers'],
                'metadata_json' => json_encode([
                    'override_reason' => $override->reason,
                    'override_note' => $override->note,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) >= $chunkSize) {
                $imported += $this->upsertRows($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $imported += $this->upsertRows($rows);
        }

        $this->components->info("Imported supplemental legacy texts: {$imported} rows.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, true>
     */
    private function types(): array
    {
        $types = array_filter(array_map(
            fn (string $type): string => trim($type),
            explode(',', (string) $this->option('types')),
        ));

        return array_fill_keys($types, true);
    }

    /**
     * @param  array<string, true>  $types
     * @return array<string, object>
     */
    private function chapterOverrides(array $types): array
    {
        return DB::table('legacy_canonical_chapter_overrides')
            ->whereIn('action', array_keys($types))
            ->get([
                'legacy_bible_id',
                'legacy_book_slug',
                'legacy_chapter_number',
                'action',
                'reason',
                'note',
            ])
            ->mapWithKeys(function ($override): array {
                $legacyBibleId = $override->legacy_bible_id === null ? '*' : (string) $override->legacy_bible_id;

                return [
                    "{$legacyBibleId}:{$override->legacy_book_slug}:{$override->legacy_chapter_number}" => $override,
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, object>  $chapterOverrides
     */
    private function chapterOverride(array $chapterOverrides, int $legacyBibleId, string $legacyBookSlug, int $legacyChapterNumber): ?object
    {
        return $chapterOverrides["{$legacyBibleId}:{$legacyBookSlug}:{$legacyChapterNumber}"]
            ?? $chapterOverrides["*:{$legacyBookSlug}:{$legacyChapterNumber}"]
            ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertRows(array $rows): int
    {
        DB::table('legacy_supplemental_texts')->upsert(
            $rows,
            ['legacy_verse_id'],
            [
                'module_id',
                'translation_id',
                'module_book_id',
                'module_chapter_id',
                'legacy_bible_id',
                'legacy_book_id',
                'legacy_chapter_id',
                'legacy_book_slug',
                'legacy_chapter_number',
                'legacy_verse_number',
                'type',
                'title',
                'text',
                'text_plain',
                'text_raw',
                'metadata_json',
                'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * @return array{text: string, plain: string}
     */
    private function normalizeText(string $rawText): array
    {
        $text = preg_replace('/^\s*\d+\s*/u', '', $rawText) ?? $rawText;
        $text = preg_replace('/\s+([,.;:!?])/u', '$1', $text) ?? $text;
        $text = preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s{2,}/u', ' ', trim($plain)) ?? trim($plain);

        return [
            'text' => $text,
            'plain' => $plain,
        ];
    }
}
