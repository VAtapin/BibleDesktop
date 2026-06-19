<?php

namespace App\Console\Commands;

use App\Support\LegacySqlDump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacyMetadata extends Command
{
    protected $signature = 'bible:legacy:import-metadata {--path=OLD/bible-desktop.sql}';

    protected $description = 'Import legacy library, book, and chapter metadata into the new module tables.';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        if (! is_file($path)) {
            $this->error("Legacy SQL dump not found: {$path}");

            return self::FAILURE;
        }

        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');

        if (! $canonId) {
            $this->error('Base orthodox canon is missing. Run database seeders first.');

            return self::FAILURE;
        }

        $reader = new LegacySqlDump($path);
        $languageIds = DB::table('languages')->pluck('id', 'code')->all();
        $canonicalBooks = DB::table('canonical_books')
            ->where('canon_id', $canonId)
            ->pluck('id', 'slug')
            ->all();

        $libraries = $this->importLibraries($reader, $languageIds, (int) $canonId);
        $books = $this->importBooks($reader, $libraries, $canonicalBooks);
        $chapters = $this->importChapters($reader, $libraries, $books);

        $this->components->info(sprintf(
            'Imported metadata: %d libraries, %d books, %d chapters.',
            $libraries['imported'],
            $books['imported'],
            $chapters['imported'],
        ));

        if ($libraries['skipped'] > 0) {
            $this->warn(sprintf('Skipped %d legacy libraries outside supported languages or non-Bible content.', $libraries['skipped']));
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, int> $languageIds
     * @return array{imported: int, skipped: int, by_id: array<int, array{module_id: int, translation_id: int}>}
     */
    private function importLibraries(LegacySqlDump $reader, array $languageIds, int $canonId): array
    {
        $now = now();
        $imported = 0;
        $skipped = 0;
        $byId = [];
        $importedLegacyIds = [];

        foreach ($reader->rows('library') as $row) {
            if (($row['bible'] ?? '') !== 'Y') {
                $skipped++;
                continue;
            }

            if (($row['oldTestament'] ?? '') !== 'Y' && ($row['newTestament'] ?? '') !== 'Y' && ($row['apocrypha'] ?? '') !== 'Y') {
                $skipped++;
                continue;
            }

            $languageCode = $this->legacyLanguageCode((string) ($row['language'] ?? ''));

            if (! $languageCode || ! isset($languageIds[$languageCode])) {
                $skipped++;
                continue;
            }

            $legacyId = (int) $row['id'];
            $importedLegacyIds[] = $legacyId;
            $code = $this->legacyCode((string) $row['bibleShortName'], $legacyId);
            $moduleType = $this->legacyModuleType((string) $row['bibleName'], (string) $row['bibleShortName']);
            $metadata = [
                'legacy_id' => $legacyId,
                'path' => $row['path'] ?? null,
                'book_qty' => $row['bookQty'] ?? null,
                'html_filter' => $row['htmlFilter'] ?? null,
                'chapter_sign' => $row['chapterSign'] ?? null,
                'verse_sign' => $row['verseSign'] ?? null,
            ];

            DB::table('modules')->updateOrInsert(
                ['code' => $code],
                [
                    'language_id' => $languageIds[$languageCode],
                    'type' => $moduleType,
                    'name' => (string) $row['bibleName'],
                    'short_name' => $this->limitString((string) $row['bibleShortName'], 80),
                    'description' => $row['description'] ?: null,
                    'version' => null,
                    'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'is_active' => (int) ($row['status'] ?? 0) === 1,
                    'is_public' => (int) ($row['published'] ?? 0) === 1,
                    'sort_order' => (int) ($row['order'] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $moduleId = (int) DB::table('modules')->where('code', $code)->value('id');

            if ($moduleType !== 'bible') {
                $this->deleteTranslationIfUnused($code);

                DB::table('legacy_libraries')->updateOrInsert(
                    ['legacy_id' => $legacyId],
                    [
                        'module_id' => $moduleId,
                        'translation_id' => null,
                        'raw_json' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $imported++;
                continue;
            }

            DB::table('translations')->updateOrInsert(
                ['code' => $code],
                [
                    'module_id' => $moduleId,
                    'language_id' => $languageIds[$languageCode],
                    'canon_id' => $canonId,
                    'name' => (string) $row['bibleName'],
                    'short_name' => $this->limitString((string) $row['bibleShortName'], 80),
                    'copyright' => null,
                    'license' => null,
                    'source' => 'legacy:bible-desktop.sql',
                    'has_old_testament' => ($row['oldTestament'] ?? '') === 'Y',
                    'has_new_testament' => ($row['newTestament'] ?? '') === 'Y',
                    'has_apocrypha' => ($row['apocrypha'] ?? '') === 'Y',
                    'has_strong' => ($row['strongNumbers'] ?? '') === 'Y',
                    'is_default' => $legacyId === 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $translationId = (int) DB::table('translations')->where('code', $code)->value('id');

            DB::table('legacy_libraries')->updateOrInsert(
                ['legacy_id' => $legacyId],
                [
                    'module_id' => $moduleId,
                    'translation_id' => $translationId,
                    'raw_json' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $byId[$legacyId] = [
                'module_id' => $moduleId,
                'translation_id' => $translationId,
            ];
            $imported++;
        }

        $this->deleteStaleLegacyLibraries($importedLegacyIds);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'by_id' => $byId,
        ];
    }

    /**
     * @param list<int> $importedLegacyIds
     */
    private function deleteStaleLegacyLibraries(array $importedLegacyIds): void
    {
        if ($importedLegacyIds === []) {
            return;
        }

        $staleLibraries = DB::table('legacy_libraries')
            ->whereNotIn('legacy_id', $importedLegacyIds)
            ->get(['legacy_id', 'module_id', 'translation_id']);

        foreach ($staleLibraries as $library) {
            DB::table('legacy_libraries')->where('legacy_id', $library->legacy_id)->delete();

            if ($library->translation_id && DB::table('verse_texts')->where('translation_id', $library->translation_id)->doesntExist()) {
                DB::table('translations')->where('id', $library->translation_id)->delete();
            }

            if ($library->module_id && DB::table('translations')->where('module_id', $library->module_id)->doesntExist()) {
                DB::table('modules')->where('id', $library->module_id)->delete();
            }
        }
    }

    /**
     * @param array{by_id: array<int, array{module_id: int, translation_id: int}>} $libraries
     * @param array<string, int> $canonicalBooks
     * @return array{imported: int, skipped: int, by_id: array<int, array{module_book_id: int, canonical_book_id: int|null}>}
     */
    private function importBooks(LegacySqlDump $reader, array $libraries, array $canonicalBooks): array
    {
        $now = now();
        $imported = 0;
        $skipped = 0;
        $byId = [];

        foreach ($reader->rows('book') as $row) {
            $legacyBibleId = (int) $row['bibleID'];

            if (! isset($libraries['by_id'][$legacyBibleId])) {
                $skipped++;
                continue;
            }

            $legacyBookId = (int) $row['bookID'];
            $slug = $this->legacyBookSlug((string) $row['bookIndex'], (string) $row['pathName'], $legacyBookId);
            $canonicalBookId = $canonicalBooks[$slug] ?? null;
            $library = $libraries['by_id'][$legacyBibleId];
            $aliases = $this->aliases((string) $row['shortName']);

            DB::table('module_books')->updateOrInsert(
                ['module_id' => $library['module_id'], 'slug' => $slug],
                [
                    'translation_id' => $library['translation_id'],
                    'canonical_book_id' => $canonicalBookId,
                    'legacy_book_id' => $legacyBookId,
                    'name' => $this->cleanLegacyBookName((string) $row['fullName']),
                    'short_name' => $this->limitString($aliases[0] ?? (string) $row['fullName'], 120),
                    'aliases_json' => json_encode($aliases, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'path_name' => $row['pathName'] ?: null,
                    'book_order' => (int) ($row['description'] ?: 0),
                    'chapters_count' => (int) $row['chapterQty'],
                    'show_verse_numbers' => (int) ($row['versNrView'] ?? 1) === 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $moduleBookId = (int) DB::table('module_books')
                ->where('module_id', $library['module_id'])
                ->where('slug', $slug)
                ->value('id');

            DB::table('legacy_books')->updateOrInsert(
                ['legacy_id' => $legacyBookId],
                [
                    'legacy_bible_id' => $legacyBibleId,
                    'module_book_id' => $moduleBookId,
                    'canonical_book_id' => $canonicalBookId,
                    'raw_json' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $byId[$legacyBookId] = [
                'module_book_id' => $moduleBookId,
                'canonical_book_id' => $canonicalBookId,
            ];
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'by_id' => $byId,
        ];
    }

    /**
     * @param array{by_id: array<int, array{module_id: int, translation_id: int}>} $libraries
     * @param array{by_id: array<int, array{module_book_id: int, canonical_book_id: int|null}>} $books
     * @return array{imported: int, skipped: int}
     */
    private function importChapters(LegacySqlDump $reader, array $libraries, array $books): array
    {
        $now = now();
        $imported = 0;
        $skipped = 0;
        $moduleChapterRows = [];
        $legacyChapterSourceRows = [];
        $moduleBookIds = [];
        $canonicalChapterVerseCounts = [];
        $canonicalChapterLookup = DB::table('canonical_chapters')
            ->get(['id', 'canonical_book_id', 'number'])
            ->mapWithKeys(fn ($chapter) => ["{$chapter->canonical_book_id}:{$chapter->number}" => (int) $chapter->id])
            ->all();

        foreach ($reader->rows('chapter') as $row) {
            $legacyBibleId = (int) $row['bibleID'];
            $legacyBookId = (int) $row['bookID'];

            if (! isset($libraries['by_id'][$legacyBibleId], $books['by_id'][$legacyBookId])) {
                $skipped++;
                continue;
            }

            $book = $books['by_id'][$legacyBookId];
            $chapterNumber = (int) $row['chapterNr'];
            $canonicalChapterId = null;

            if ($book['canonical_book_id']) {
                $canonicalChapterId = $canonicalChapterLookup["{$book['canonical_book_id']}:{$chapterNumber}"] ?? null;
            }

            $moduleBookId = $book['module_book_id'];
            $moduleBookIds[$moduleBookId] = true;
            $moduleChapterRows[] = [
                'module_book_id' => $moduleBookId,
                'canonical_chapter_id' => $canonicalChapterId,
                'legacy_chapter_id' => (int) $row['chapterID'],
                'chapter_number' => $chapterNumber,
                'title' => $row['title'] ?: null,
                'verses_count' => (int) $row['verseQty'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($canonicalChapterId) {
                $canonicalChapterVerseCounts[$canonicalChapterId] ??= (int) $row['verseQty'];
            }

            $legacyChapterSourceRows[] = [
                'legacy_id' => (int) $row['chapterID'],
                'legacy_book_id' => $legacyBookId,
                'legacy_bible_id' => $legacyBibleId,
                'module_book_id' => $moduleBookId,
                'chapter_number' => $chapterNumber,
                'canonical_chapter_id' => $canonicalChapterId,
                'raw_json' => null,
            ];

            $imported++;
        }

        foreach (array_chunk($moduleChapterRows, 500) as $chunk) {
            DB::table('module_chapters')->upsert(
                $chunk,
                ['module_book_id', 'chapter_number'],
                ['canonical_chapter_id', 'legacy_chapter_id', 'title', 'verses_count', 'updated_at'],
            );
        }

        $moduleChapterIds = DB::table('module_chapters')
            ->whereIn('module_book_id', array_keys($moduleBookIds))
            ->get(['id', 'module_book_id', 'chapter_number'])
            ->mapWithKeys(fn ($chapter) => ["{$chapter->module_book_id}:{$chapter->chapter_number}" => (int) $chapter->id])
            ->all();

        $legacyChapterRows = [];

        foreach ($legacyChapterSourceRows as $row) {
            $moduleChapterId = $moduleChapterIds["{$row['module_book_id']}:{$row['chapter_number']}"] ?? null;

            if (! $moduleChapterId) {
                continue;
            }

            $legacyChapterRows[] = [
                'legacy_id' => $row['legacy_id'],
                'legacy_book_id' => $row['legacy_book_id'],
                'legacy_bible_id' => $row['legacy_bible_id'],
                'module_chapter_id' => $moduleChapterId,
                'canonical_chapter_id' => $row['canonical_chapter_id'],
                'raw_json' => $row['raw_json'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($legacyChapterRows, 500) as $chunk) {
            DB::table('legacy_chapters')->upsert(
                $chunk,
                ['legacy_id'],
                ['legacy_book_id', 'legacy_bible_id', 'module_chapter_id', 'canonical_chapter_id', 'raw_json', 'updated_at'],
            );
        }

        foreach ($canonicalChapterVerseCounts as $canonicalChapterId => $versesCount) {
            DB::table('canonical_chapters')
                ->where('id', $canonicalChapterId)
                ->where('verses_count', 0)
                ->update([
                    'verses_count' => $versesCount,
                    'updated_at' => $now,
                ]);
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    private function legacyLanguageCode(string $language): ?string
    {
        return match (mb_strtolower(trim($language))) {
            'русский' => 'ru',
            'украинский' => 'uk',
            'белорусский' => 'be',
            'deutsch' => 'de',
            'english' => 'en',
            default => null,
        };
    }

    private function legacyModuleType(string $name, string $shortName): string
    {
        $normalizedName = mb_strtolower($name);
        $normalizedShortName = mb_strtolower($shortName);

        if ($normalizedShortName === 'lop' || str_contains($normalizedName, 'лопухин') || str_contains($normalizedName, 'толков')) {
            return 'commentary';
        }

        return 'bible';
    }

    private function deleteTranslationIfUnused(string $code): void
    {
        $translation = DB::table('translations')->where('code', $code)->first(['id']);

        if (! $translation) {
            return;
        }

        if (DB::table('verse_texts')->where('translation_id', $translation->id)->exists()) {
            return;
        }

        DB::table('translations')->where('id', $translation->id)->delete();
    }

    private function legacyCode(string $shortName, int $legacyId): string
    {
        $slug = Str::slug($shortName) ?: 'module';

        return strtoupper($this->limitString("L{$legacyId}_{$slug}", 40));
    }

    private function legacyBookSlug(string $bookIndex, string $pathName, int $legacyBookId): string
    {
        $slug = $bookIndex !== ''
            ? $bookIndex
            : (Str::slug(pathinfo($pathName, PATHINFO_FILENAME)) ?: "book-{$legacyBookId}");

        return match ($slug) {
            'revelations', 're', 'rev', 'nrt-66' => 'revelation',
            '67-mak1', 'slr67', '1ma' => '1maccabees',
            '68-mak2', 'slr68', '2ma' => '2maccabees',
            '3ma' => '3maccabees',
            '69-var', 'slr70' => 'baruch',
            '70-jdt', 'slr73' => 'judith',
            '71-lyst', 'slr74' => 'epistle',
            '72-mudr', 'slr75' => 'wisdom',
            '73-syr', 'slr76' => 'sirach',
            '74-tov', 'slr77' => 'tobit',
            'slr71' => '2esdras',
            'slr72' => '3esdras',
            default => match ($legacyBookId) {
                69 => '3maccabees',
                72 => '3esdras',
                default => $slug,
            },
        };
    }

    private function cleanLegacyBookName(string $name): string
    {
        return trim(str_replace('(неканон.)', '', $name));
    }

    /**
     * @return list<string>
     */
    private function aliases(string $shortName): array
    {
        $aliases = preg_split('/\s+/u', trim($shortName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($aliases));
    }

    private function limitString(string $value, int $limit): string
    {
        return mb_substr($value, 0, $limit);
    }
}
