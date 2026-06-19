<?php

namespace App\Console\Commands;

use App\Support\LegacySqlDump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportSkippedLegacyVerses extends Command
{
    protected $signature = 'bible:legacy:report-skipped-verses
        {--path=OLD/bible-desktop.sql}
        {--limit=25 : Number of grouped skipped rows to show}';

    protected $description = 'Report legacy verse rows that cannot be imported because metadata/canonical mappings are missing.';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        if (! is_file($path)) {
            $this->error("Legacy SQL dump not found: {$path}");

            return self::FAILURE;
        }

        $libraries = DB::table('legacy_libraries')
            ->leftJoin('translations', 'translations.id', '=', 'legacy_libraries.translation_id')
            ->whereNotNull('legacy_libraries.translation_id')
            ->get([
                'legacy_libraries.legacy_id',
                'translations.code as translation_code',
            ])
            ->mapWithKeys(fn ($library) => [
                (int) $library->legacy_id => [
                    'translation_code' => (string) $library->translation_code,
                ],
            ])
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
                'legacy_books.canonical_book_id',
                'module_books.slug',
                'module_books.name',
            ])
            ->keyBy('legacy_id');

        $chapterMap = DB::table('legacy_chapters')
            ->join('module_chapters', 'module_chapters.id', '=', 'legacy_chapters.module_chapter_id')
            ->get([
                'legacy_chapters.legacy_id',
                'legacy_chapters.legacy_bible_id',
                'legacy_chapters.canonical_chapter_id',
                'module_chapters.chapter_number',
                'module_chapters.title',
                'module_chapters.verses_count',
            ])
            ->keyBy('legacy_id');

        $chapterOverrides = $this->chapterOverrides();
        $verseOverrides = $this->verseOverrides();
        $groups = [];
        $libraryTotals = [];
        $totals = [];

        foreach ((new LegacySqlDump($path))->rows('verse') as $row) {
            $libraryId = (int) $row['bibleID'];

            if (! isset($libraries[$libraryId])) {
                continue;
            }

            $legacyBookId = (int) $row['bookID'];
            $legacyChapterId = (int) $row['chapterID'];
            $book = $bookMap[$legacyBookId] ?? null;
            $chapter = $chapterMap[$legacyChapterId] ?? null;
            $reason = null;

            if (! $book) {
                $reason = 'missing_book';
            } elseif (! $book->canonical_book_id) {
                $reason = 'missing_canonical_book';
            } elseif (! $chapter) {
                $reason = 'missing_chapter';
            } elseif (! $chapter->canonical_chapter_id) {
                if ($this->verseOverride($verseOverrides, $libraryId, (string) $book->slug, (int) $chapter->chapter_number, (int) $row['verseNr'])) {
                    continue;
                }

                $override = $this->chapterOverride($chapterOverrides, $libraryId, (string) $book->slug, (int) $chapter->chapter_number);
                $reason = $override ? "override_{$override->action}" : 'missing_canonical_chapter';
            }

            if (! $reason) {
                continue;
            }

            $totals[$reason] ??= 0;
            $totals[$reason]++;
            $libraryTotals[$libraryId] ??= [
                'library' => $libraryId,
                'translation' => $libraries[$libraryId]['translation_code'],
                'verses' => 0,
            ];
            $libraryTotals[$libraryId]['verses']++;
            $key = implode(':', [$reason, $libraryId, $legacyBookId, $legacyChapterId]);

            $groups[$key] ??= [
                'reason' => $reason,
                'library' => $libraryId,
                'translation' => $libraries[$libraryId]['translation_code'],
                'book' => $book?->slug ?? "legacy-book-{$legacyBookId}",
                'book_name' => $book?->name ?? '',
                'chapter' => $chapter?->chapter_number ?? '?',
                'title' => $chapter?->title ?? '',
                'legacy_chapter' => $legacyChapterId,
                'verses' => 0,
            ];
            $groups[$key]['verses']++;
        }

        $skipped = array_sum($totals);

        $this->components->info("Skipped legacy verses: {$skipped}");

        foreach ($totals as $reason => $count) {
            if ($count > 0) {
                $this->line("{$reason}: {$count}");
            }
        }

        usort($groups, fn (array $left, array $right): int => $right['verses'] <=> $left['verses']);
        usort($libraryTotals, fn (array $left, array $right): int => $right['verses'] <=> $left['verses']);

        $rows = array_slice($groups, 0, max(1, (int) $this->option('limit')));

        if ($libraryTotals !== []) {
            $this->table(
                ['library', 'translation', 'skipped_verses'],
                array_map(fn (array $row): array => [
                    $row['library'],
                    $row['translation'],
                    $row['verses'],
                ], array_slice($libraryTotals, 0, 10)),
            );
        }

        if ($rows !== []) {
            $this->table(
                ['reason', 'library', 'translation', 'book', 'chapter', 'legacy_chapter', 'verses', 'book_name'],
                array_map(fn (array $row): array => [
                    $row['reason'],
                    $row['library'],
                    $row['translation'],
                    $row['book'],
                    $row['chapter'],
                    $row['legacy_chapter'],
                    $row['verses'],
                    trim($row['book_name'].' '.$row['title']),
                ], $rows),
            );
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, object>
     */
    private function chapterOverrides(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('legacy_canonical_chapter_overrides')) {
            return [];
        }

        return DB::table('legacy_canonical_chapter_overrides')
            ->get([
                'legacy_bible_id',
                'legacy_book_slug',
                'legacy_chapter_number',
                'action',
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
     * @param array<string, object> $chapterOverrides
     */
    private function chapterOverride(array $chapterOverrides, int $legacyBibleId, string $legacyBookSlug, int $legacyChapterNumber): ?object
    {
        return $chapterOverrides["{$legacyBibleId}:{$legacyBookSlug}:{$legacyChapterNumber}"]
            ?? $chapterOverrides["*:{$legacyBookSlug}:{$legacyChapterNumber}"]
            ?? null;
    }

    /**
     * @return array<string, true>
     */
    private function verseOverrides(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('legacy_canonical_verse_overrides')) {
            return [];
        }

        return DB::table('legacy_canonical_verse_overrides')
            ->where('action', 'map_verse')
            ->get([
                'legacy_bible_id',
                'legacy_book_slug',
                'legacy_chapter_number',
                'legacy_verse_number',
            ])
            ->mapWithKeys(function ($override): array {
                $legacyBibleId = $override->legacy_bible_id === null ? '*' : (string) $override->legacy_bible_id;

                return [
                    "{$legacyBibleId}:{$override->legacy_book_slug}:{$override->legacy_chapter_number}:{$override->legacy_verse_number}" => true,
                ];
            })
            ->all();
    }

    /**
     * @param array<string, true> $verseOverrides
     */
    private function verseOverride(array $verseOverrides, int $legacyBibleId, string $legacyBookSlug, int $legacyChapterNumber, int $legacyVerseNumber): bool
    {
        return isset($verseOverrides["{$legacyBibleId}:{$legacyBookSlug}:{$legacyChapterNumber}:{$legacyVerseNumber}"])
            || isset($verseOverrides["*:{$legacyBookSlug}:{$legacyChapterNumber}:{$legacyVerseNumber}"]);
    }
}
