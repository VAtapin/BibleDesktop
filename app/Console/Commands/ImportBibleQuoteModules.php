<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class ImportBibleQuoteModules extends Command
{
    protected $signature = 'bible:bq:import
        {--path= : Import one BibleQuote ZIP module}
        {--dir=OLD/Mod : Import all ZIP/MyBible SQLite modules from a directory}
        {--languages=ru,de,en,uk : Comma-separated language codes allowed for directory imports}
        {--replace : Delete all existing Bible translations before import}
        {--include-non-bible : Import metadata for non-Bible modules later; currently skipped}
        {--chunk=500 : Database upsert chunk size}';

    protected $description = 'Import BibleQuote ZIP and MyBible SQLite Bible modules into translations, books, chapters, and verse texts.';

    private const BOOK_SLUG_BY_KEY = [
        'genesis' => 'genesis',
        'exodus' => 'exodus',
        'leviticus' => 'leviticus',
        'numbers' => 'numbers',
        'deuteronomy' => 'deuteronomy',
        'joshua' => 'joshua',
        'judges' => 'judges',
        'ruth' => 'ruth',
        '1samuel' => '1samuel',
        '2samuel' => '2samuel',
        '1kings' => '1kings',
        '2kings' => '2kings',
        '1chronicles' => '1chron',
        '1chron' => '1chron',
        '2chronicles' => '2chron',
        '2chron' => '2chron',
        'ezra' => 'ezra',
        'nehemiah' => 'nehemiah',
        'esther' => 'esther',
        'job' => 'job',
        'psalms' => 'psalms',
        'psalm' => 'psalms',
        'proverbs' => 'proverbs',
        'ecclesiastes' => 'ecclesia',
        'ecclesia' => 'ecclesia',
        'songofsolomon' => 'songs',
        'songofsongs' => 'songs',
        'songs' => 'songs',
        'isaiah' => 'isaiah',
        'jeremiah' => 'jeremiah',
        'lamentations' => 'lamentations',
        'ezekiel' => 'ezekiel',
        'daniel' => 'daniel',
        'hosea' => 'hosea',
        'joel' => 'joel',
        'amos' => 'amos',
        'obadiah' => 'obadiah',
        'jonah' => 'jonah',
        'micah' => 'micah',
        'nahum' => 'nahum',
        'habakkuk' => 'habakkuk',
        'zephaniah' => 'zephaniah',
        'haggai' => 'haggai',
        'zechariah' => 'zechariah',
        'malachi' => 'malachi',
        'matthew' => 'matthew',
        'mark' => 'mark',
        'luke' => 'luke',
        'john' => 'john',
        'acts' => 'acts',
        'james' => 'james',
        '1peter' => '1peter',
        '2peter' => '2peter',
        '1john' => '1john',
        '2john' => '2john',
        '3john' => '3john',
        'jude' => 'jude',
        'romans' => 'romans',
        '1corinthians' => '1corinthians',
        '2corinthians' => '2corinthians',
        'galatians' => 'galatians',
        'ephesians' => 'ephesians',
        'philippians' => 'philippians',
        'colossians' => 'colossians',
        '1thessalonians' => '1thessalonians',
        '2thessalonians' => '2thessalonians',
        '1timothy' => '1timothy',
        '2timothy' => '2timothy',
        'titus' => 'titus',
        'philemon' => 'philemon',
        'hebrews' => 'hebrews',
        'revelation' => 'revelation',
        '1maccabees' => '1maccabees',
        '2maccabees' => '2maccabees',
        '3maccabees' => '3maccabees',
        'baruch' => 'baruch',
        '2esdras' => '2esdras',
        '3esdras' => '3esdras',
        'judith' => 'judith',
        'epistle' => 'epistle',
        'epistleofjeremiah' => 'epistle',
        'wisdom' => 'wisdom',
        'sirach' => 'sirach',
        'tobit' => 'tobit',
    ];

    public function handle(): int
    {
        $paths = $this->modulePaths();

        if ($paths === []) {
            $this->error('No ZIP modules found.');

            return self::FAILURE;
        }

        if ((bool) $this->option('replace')) {
            $this->deleteExistingBibleModules();
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($paths as $path) {
            try {
                $result = $this->importPath($path);

                if ($result['skipped']) {
                    $skipped++;
                    $this->line("Skipped: {$path} ({$result['reason']})");

                    continue;
                }

                $imported++;
                $this->components->info(sprintf(
                    'Imported %s: %s, %d books, %d verse texts.',
                    basename($path),
                    $result['code'],
                    $result['books'],
                    $result['verses'],
                ));
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Failed: {$path} - {$e->getMessage()}");
            }
        }

        $this->components->info("BibleQuote import finished: {$imported} imported, {$skipped} skipped, {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<string>
     */
    private function modulePaths(): array
    {
        $path = trim((string) $this->option('path'));

        if ($path !== '') {
            $resolved = is_file($path) ? $path : base_path($path);

            return is_file($resolved) ? [$resolved] : [];
        }

        $dir = base_path((string) $this->option('dir'));
        $patterns = [
            $dir.DIRECTORY_SEPARATOR.'*.zip',
            $dir.DIRECTORY_SEPARATOR.'*.SQLite3',
            $dir.DIRECTORY_SEPARATOR.'*.sqlite3',
        ];
        $paths = [];

        foreach ($patterns as $pattern) {
            $paths = array_merge($paths, glob($pattern) ?: []);
        }

        sort($paths, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_unique($paths));
    }

    private function deleteExistingBibleModules(): void
    {
        DB::transaction(function (): void {
            $moduleIds = DB::table('modules')->where('type', 'bible')->pluck('id')->all();

            if ($moduleIds === []) {
                return;
            }

            DB::table('modules')->whereIn('id', $moduleIds)->delete();
        });

        $this->warn('Deleted existing Bible modules and translations.');
    }

    /**
     * @return array{skipped: bool, reason?: string, code?: string, books?: int, verses?: int}
     */
    private function importPath(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'zip' => $this->importZip($path),
            'sqlite3', 'sqlite' => $this->importMyBibleSqlite($path),
            default => ['skipped' => true, 'reason' => 'unsupported module file type'],
        };
    }

    /**
     * @return array{skipped: bool, reason?: string, code?: string, books?: int, verses?: int}
     */
    private function importZip(string $path): array
    {
        if (str_starts_with(basename($path), 'BibleQuote_')) {
            return ['skipped' => true, 'reason' => 'BibleQuote program bundle, not a single module'];
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Cannot open ZIP.');
        }

        try {
            $iniPath = $this->findIniPath($zip);

            if (! $iniPath) {
                return ['skipped' => true, 'reason' => 'bibleqt.ini not found'];
            }

            $ini = $this->parseIni($this->readZipText($zip, $iniPath));

            if (($ini['meta']['Bible'] ?? 'N') !== 'Y') {
                return ['skipped' => true, 'reason' => 'not a Bible module'];
            }

            $languageCode = $this->languageCode($path, $ini['meta']);

            if (! $this->languageAllowed($languageCode)) {
                return ['skipped' => true, 'reason' => "language {$languageCode} is not enabled"];
            }

            $books = $this->mappedBooks($ini['books']);

            if ($books === []) {
                return ['skipped' => true, 'reason' => 'no canonical Bible books mapped'];
            }

            $missingEntries = $this->missingZipBookEntries($zip, dirname($iniPath), $books);

            if ($missingEntries !== []) {
                return [
                    'skipped' => true,
                    'reason' => sprintf('missing book files: %s', implode(', ', array_slice($missingEntries, 0, 5))),
                ];
            }

            $result = DB::transaction(function () use ($path, $ini, $books, $zip, $iniPath): array {
                $translation = $this->upsertTranslation($path, $ini['meta']);
                $bookResult = $this->upsertBooksAndChapters($translation, $books);
                $verseCount = $this->importVerses($zip, dirname($iniPath), $translation, $bookResult['books'], $books, $ini['meta']);

                return [
                    'code' => $translation['code'],
                    'books' => count($bookResult['books']),
                    'verses' => $verseCount,
                ];
            });

            return [
                'skipped' => false,
                'code' => $result['code'],
                'books' => $result['books'],
                'verses' => $result['verses'],
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{skipped: bool, reason?: string, code?: string, books?: int, verses?: int}
     */
    private function importMyBibleSqlite(string $path): array
    {
        $pdo = new \PDO('sqlite:'.$path);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        if (! $this->sqliteHasTables($pdo, ['books', 'info', 'verses'])) {
            return ['skipped' => true, 'reason' => 'not a MyBible SQLite Bible module'];
        }

        $meta = $this->readSqliteInfo($pdo);
        $languageCode = (string) ($meta['language'] ?? $this->languageCode($path, []));

        if (! $this->languageAllowed($languageCode)) {
            return ['skipped' => true, 'reason' => "language {$languageCode} is not enabled"];
        }

        $books = $this->mappedSqliteBooks($pdo);

        if ($books === []) {
            return ['skipped' => true, 'reason' => 'no canonical Bible books mapped'];
        }

        $result = DB::transaction(function () use ($path, $meta, $books, $pdo): array {
            $translation = $this->upsertTranslation($path, [
                'BibleName' => (string) ($meta['description'] ?? pathinfo($path, PATHINFO_FILENAME)),
                'BibleShortName' => pathinfo($path, PATHINFO_FILENAME),
                'OldTestament' => collect($books)->contains(fn (array $book): bool => (int) $book['book_order'] <= 39) ? 'Y' : 'N',
                'NewTestament' => collect($books)->contains(fn (array $book): bool => (int) $book['book_order'] >= 40) ? 'Y' : 'N',
                'Apocrypha' => 'N',
                'StrongNumbers' => ((string) ($meta['strong_numbers'] ?? 'false')) === 'true' ? 'Y' : 'N',
                'ModuleVersion' => null,
                'DefaultEncoding' => 'UTF-8',
            ]);
            $bookResult = $this->upsertBooksAndChapters($translation, $books);
            $verseCount = $this->importSqliteVerses($pdo, $translation, $bookResult['books'], $books);

            return [
                'code' => $translation['code'],
                'books' => count($bookResult['books']),
                'verses' => $verseCount,
            ];
        });

        return [
            'skipped' => false,
            'code' => $result['code'],
            'books' => $result['books'],
            'verses' => $result['verses'],
        ];
    }

    private function findIniPath(ZipArchive $zip): ?string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (preg_match('/(?:^|\/)bibleqt\.ini$/iu', $name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $books
     * @return list<string>
     */
    private function missingZipBookEntries(ZipArchive $zip, string $baseDir, array $books): array
    {
        $missing = [];

        foreach ($books as $book) {
            $entryPath = trim($baseDir.'/'.$book['path_name'], '/');

            if ($this->findZipEntry($zip, $entryPath) === null) {
                $missing[] = $book['path_name'];
            }
        }

        return $missing;
    }

    private function findZipEntry(ZipArchive $zip, string $path): ?string
    {
        $normalizedPath = $this->normalizeZipPath($path);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if ($this->normalizeZipPath($name) === $normalizedPath) {
                return $name;
            }
        }

        return null;
    }

    private function normalizeZipPath(string $path): string
    {
        return strtolower(trim(str_replace('\\', '/', $path), '/'));
    }

    private function readZipText(ZipArchive $zip, string $path, string $encoding = 'UTF-8'): string
    {
        $resolvedPath = $this->findZipEntry($zip, $path) ?? $path;
        $content = $zip->getFromName($resolvedPath);

        if ($content === false) {
            throw new \RuntimeException("ZIP entry not found: {$path}");
        }

        return $this->toUtf8($content, $encoding);
    }

    private function toUtf8(string $value, string $encoding): string
    {
        $encoding = strtoupper(trim($encoding) ?: 'UTF-8');
        $encoding = match ($encoding) {
            'WINDOWS-1251', 'CP1251', 'WIN-1251' => 'Windows-1251',
            'WINDOWS-1252', 'CP1252', 'WIN-1252' => 'Windows-1252',
            'ANSI' => 'Windows-1251',
            default => $encoding,
        };

        if (strtoupper($encoding) !== 'UTF-8') {
            $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);

            if (is_string($converted)) {
                return $converted;
            }
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', ['Windows-1251', 'Windows-1252', 'ISO-8859-1']);
    }

    /**
     * @param  list<string>  $tables
     */
    private function sqliteHasTables(\PDO $pdo, array $tables): bool
    {
        $existing = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")
            ?->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $existing = array_map('strtolower', array_map('strval', $existing));

        foreach ($tables as $table) {
            if (! in_array(strtolower($table), $existing, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function readSqliteInfo(\PDO $pdo): array
    {
        $rows = $pdo->query('SELECT name, value FROM info')?->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

        return array_map('strval', $rows);
    }

    /**
     * @return array{meta: array<string, string>, books: list<array<string, string>>}
     */
    private function parseIni(string $content): array
    {
        $meta = [];
        $books = [];
        $current = null;

        foreach (preg_split('/\R/u', $content) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, ';') || str_starts_with($line, '//')) {
                continue;
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            if ($key === 'PathName') {
                if ($current !== null) {
                    $books[] = $current;
                }

                $current = ['PathName' => $value];

                continue;
            }

            if ($current !== null && in_array($key, ['FullName', 'ShortName', 'ChapterQty'], true)) {
                $current[$key] = $value;

                continue;
            }

            $meta[$key] = $value;
        }

        if ($current !== null) {
            $books[] = $current;
        }

        return ['meta' => $meta, 'books' => $books];
    }

    /**
     * @param  list<array<string, string>>  $books
     * @return list<array<string, mixed>>
     */
    private function mappedBooks(array $books): array
    {
        $canonicalByOrder = DB::table('canonical_books')
            ->orderBy('canonical_order')
            ->get(['id', 'slug', 'osis_code', 'canonical_order'])
            ->values();
        $canonicalBySlug = $canonicalByOrder->keyBy('slug');
        $mapped = [];

        foreach ($books as $index => $book) {
            $pathName = (string) ($book['PathName'] ?? '');
            $slug = $this->slugFromPath($pathName);
            $canonical = $slug ? $canonicalBySlug->get($slug) : null;

            if (! $canonical && preg_match('/^(?:ru)?(\d{1,2})/i', basename($pathName), $matches)) {
                $canonical = $canonicalByOrder->get(((int) $matches[1]) - 1);
            }

            if (! $canonical) {
                $canonical = $canonicalByOrder->get($index);
            }

            if (! $canonical) {
                continue;
            }

            $mapped[] = [
                'path_name' => $pathName,
                'name' => (string) ($book['FullName'] ?? $canonical->slug),
                'short_name' => (string) ($book['ShortName'] ?? ''),
                'chapters_count' => (int) ($book['ChapterQty'] ?? 0),
                'canonical_book_id' => (int) $canonical->id,
                'slug' => (string) $canonical->slug,
                'osis_code' => $canonical->osis_code ? (string) $canonical->osis_code : null,
                'book_order' => (int) $canonical->canonical_order,
            ];
        }

        return $mapped;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mappedSqliteBooks(\PDO $pdo): array
    {
        $canonicalByOrder = DB::table('canonical_books')
            ->orderBy('canonical_order')
            ->get(['id', 'slug', 'osis_code', 'canonical_order'])
            ->values();
        $bookRows = $pdo->query('SELECT book_number, short_name, long_name FROM books ORDER BY book_number')
            ?->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $chapterRows = $pdo->query('SELECT book_number, MAX(chapter) AS chapters_count FROM verses GROUP BY book_number')
            ?->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
        $mapped = [];

        foreach ($bookRows as $index => $book) {
            $canonical = $canonicalByOrder->get($index);

            if (! $canonical) {
                continue;
            }

            $bookNumber = (int) $book['book_number'];
            $mapped[] = [
                'path_name' => (string) $bookNumber,
                'name' => (string) ($book['long_name'] ?: $canonical->slug),
                'short_name' => (string) ($book['short_name'] ?? ''),
                'chapters_count' => (int) ($chapterRows[$bookNumber] ?? 0),
                'canonical_book_id' => (int) $canonical->id,
                'slug' => (string) $canonical->slug,
                'osis_code' => $canonical->osis_code ? (string) $canonical->osis_code : null,
                'book_order' => (int) $canonical->canonical_order,
                'sqlite_book_number' => $bookNumber,
            ];
        }

        return $mapped;
    }

    private function slugFromPath(string $pathName): ?string
    {
        $base = strtolower(pathinfo($pathName, PATHINFO_FILENAME));
        $base = preg_replace('/^\d+[_-]*/', '', $base) ?? $base;
        $key = preg_replace('/[^a-z0-9]+/', '', $base) ?? $base;

        return self::BOOK_SLUG_BY_KEY[$key] ?? null;
    }

    /**
     * @param  array<string, string>  $meta
     * @return array{id: int, module_id: int, code: string}
     */
    private function upsertTranslation(string $path, array $meta): array
    {
        $now = now();
        $languageCode = $this->languageCode($path, $meta);
        $languageId = DB::table('languages')->where('code', $languageCode)->value('id')
            ?: DB::table('languages')->where('code', 'en')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $code = $this->moduleCode($path);
        $name = trim((string) ($meta['BibleName'] ?? $meta['ModuleName'] ?? $code));
        $shortName = trim((string) ($meta['BibleShortName'] ?? $code));

        DB::table('modules')->updateOrInsert(
            ['code' => $code],
            [
                'language_id' => $languageId,
                'type' => 'bible',
                'name' => $name,
                'short_name' => Str::limit($shortName, 80, ''),
                'description' => null,
                'version' => $meta['ModuleVersion'] ?? null,
                'metadata_json' => json_encode([
                    'source_zip' => basename($path),
                    'module_author' => $meta['ModuleAuthor'] ?? null,
                    'module_compiler' => $meta['ModuleCompiler'] ?? null,
                    'encoding' => $meta['DefaultEncoding'] ?? 'UTF-8',
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'is_active' => true,
                'is_public' => true,
                'sort_order' => $code === 'BQ_RUSSIAN_RST' ? 1 : 100,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $moduleId = (int) DB::table('modules')->where('code', $code)->value('id');

        DB::table('translations')->updateOrInsert(
            ['code' => $code],
            [
                'module_id' => $moduleId,
                'language_id' => $languageId,
                'canon_id' => $canonId,
                'name' => $name,
                'short_name' => Str::limit($shortName, 80, ''),
                'copyright' => $meta['Copyright'] ?? null,
                'license' => null,
                'source' => 'biblequote:'.basename($path),
                'has_old_testament' => ($meta['OldTestament'] ?? 'N') === 'Y',
                'has_new_testament' => ($meta['NewTestament'] ?? 'N') === 'Y',
                'has_apocrypha' => ($meta['Apocrypha'] ?? 'N') === 'Y',
                'has_strong' => ($meta['StrongNumbers'] ?? 'N') === 'Y',
                'is_default' => $code === 'BQ_RUSSIAN_RST',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        return [
            'id' => (int) DB::table('translations')->where('code', $code)->value('id'),
            'module_id' => $moduleId,
            'code' => $code,
        ];
    }

    private function moduleCode(string $path): string
    {
        $base = pathinfo($path, PATHINFO_FILENAME);
        $base = preg_replace('/_\d{4}-\d{2}-\d{2}$/', '', $base) ?? $base;
        $base = preg_replace('/^Bible_/', '', $base) ?? $base;
        $base = preg_replace('/[^A-Za-z0-9]+/', '_', $base) ?? $base;
        $base = strtoupper(trim($base, '_')) ?: strtoupper(substr(sha1($path), 0, 12));

        return Str::limit('BQ_'.$base, 40, '');
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function languageCode(string $path, array $meta): string
    {
        $name = basename($path);

        return match (true) {
            str_contains($name, 'Russian') || str_starts_with($name, 'B_Russian') => 'ru',
            str_contains($name, 'Ukrain') || str_contains($name, 'Ukraine') => 'uk',
            str_contains($name, 'German') => 'de',
            str_contains($name, 'English') || str_contains($name, '0En') => 'en',
            str_contains($name, 'French') => 'fr',
            str_contains($name, 'Espanol') => 'es',
            str_contains($name, 'Estonian') => 'et',
            str_contains($name, 'Armenian') => 'hy',
            str_contains($name, 'Azerbajan') => 'az',
            str_contains($name, 'Greek') || str_contains($name, 'LXX') => 'el',
            str_contains($name, 'Hebrew') => 'he',
            str_contains($name, 'Kazakh') => 'kk',
            str_contains($name, 'Latin') => 'la',
            str_contains($name, 'Latvian') => 'lv',
            str_contains($name, 'Lithuanian') => 'lt',
            str_contains($name, 'Macedonian') => 'mk',
            str_contains($name, 'Poland') => 'pl',
            str_contains($name, 'Serbia') => 'sr',
            str_contains($name, 'Harvat') => 'hr',
            str_contains($name, 'Slovak') => 'sk',
            str_contains($name, 'Slovenian') => 'sl',
            str_contains($name, 'Suomi') => 'fi',
            str_contains($name, 'Sweden') => 'sv',
            str_contains($name, 'Turkish') => 'tr',
            str_contains($name, 'Uzbek') => 'uz',
            default => 'en',
        };
    }

    private function languageAllowed(string $languageCode): bool
    {
        $allowed = array_filter(array_map(
            fn (string $language): string => strtolower(trim($language)),
            explode(',', (string) $this->option('languages')),
        ));

        if ($allowed === []) {
            return true;
        }

        return in_array(strtolower($languageCode), $allowed, true);
    }

    /**
     * @param  array{id: int, module_id: int, code: string}  $translation
     * @param  list<array<string, mixed>>  $books
     * @return array{books: array<string, array{id: int, chapters: array<int, int>}>}
     */
    private function upsertBooksAndChapters(array $translation, array $books): array
    {
        $now = now();
        $moduleBooks = [];

        foreach ($books as $book) {
            DB::table('module_books')->updateOrInsert(
                ['module_id' => $translation['module_id'], 'slug' => $book['slug']],
                [
                    'translation_id' => $translation['id'],
                    'canonical_book_id' => $book['canonical_book_id'],
                    'name' => $book['name'],
                    'short_name' => Str::limit($book['short_name'], 120, ''),
                    'aliases_json' => json_encode(array_values(array_filter(explode(' ', $book['short_name']))), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'path_name' => $book['path_name'],
                    'book_order' => $book['book_order'],
                    'chapters_count' => $book['chapters_count'],
                    'show_verse_numbers' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $moduleBookId = (int) DB::table('module_books')
                ->where('module_id', $translation['module_id'])
                ->where('slug', $book['slug'])
                ->value('id');
            $moduleBooks[$book['path_name']] = [
                'id' => $moduleBookId,
                'chapters' => [],
            ];

            for ($chapter = 1; $chapter <= max(1, (int) $book['chapters_count']); $chapter++) {
                $canonicalChapterId = DB::table('canonical_chapters')
                    ->where('canonical_book_id', $book['canonical_book_id'])
                    ->where('number', $chapter)
                    ->value('id');

                DB::table('module_chapters')->updateOrInsert(
                    ['module_book_id' => $moduleBookId, 'chapter_number' => $chapter],
                    [
                        'canonical_chapter_id' => $canonicalChapterId ?: null,
                        'title' => null,
                        'verses_count' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $moduleBooks[$book['path_name']]['chapters'][$chapter] = (int) DB::table('module_chapters')
                    ->where('module_book_id', $moduleBookId)
                    ->where('chapter_number', $chapter)
                    ->value('id');
            }
        }

        return ['books' => $moduleBooks];
    }

    /**
     * @param  array{id: int, module_id: int, code: string}  $translation
     * @param  array<string, array{id: int, chapters: array<int, int>}>  $moduleBooks
     * @param  list<array<string, mixed>>  $books
     * @param  array<string, string>  $meta
     */
    private function importVerses(ZipArchive $zip, string $baseDir, array $translation, array $moduleBooks, array $books, array $meta): int
    {
        $now = now();
        $chunkSize = max(100, (int) $this->option('chunk'));
        $encoding = $meta['DefaultEncoding'] ?? 'UTF-8';
        $canonicalChapters = DB::table('canonical_chapters')
            ->get(['id', 'canonical_book_id', 'number'])
            ->mapWithKeys(fn ($chapter) => ["{$chapter->canonical_book_id}:{$chapter->number}" => (int) $chapter->id])
            ->all();
        $verseRows = [];
        $textRows = [];
        $imported = 0;

        foreach ($books as $book) {
            $entryPath = trim($baseDir.'/'.$book['path_name'], '/');
            $rawHtml = $this->readZipText($zip, $entryPath, $encoding);
            $parsed = $this->parseBookHtml($rawHtml);
            $moduleBook = $moduleBooks[$book['path_name']] ?? null;

            if (! $moduleBook) {
                continue;
            }

            foreach ($parsed as $chapterNumber => $verses) {
                $canonicalChapterId = $canonicalChapters["{$book['canonical_book_id']}:{$chapterNumber}"] ?? null;
                $moduleChapterId = $moduleBook['chapters'][$chapterNumber] ?? null;

                if (! $canonicalChapterId || ! $moduleChapterId) {
                    continue;
                }

                foreach ($verses as $verseNumber => $rawVerse) {
                    $verseRows[] = [
                        'canonical_book_id' => $book['canonical_book_id'],
                        'canonical_chapter_id' => $canonicalChapterId,
                        'chapter_number' => $chapterNumber,
                        'verse_number' => $verseNumber,
                        'osis_ref' => $book['osis_code'] ? "{$book['osis_code']}.{$chapterNumber}.{$verseNumber}" : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $textRows[] = [
                        'book' => $book,
                        'module_book_id' => $moduleBook['id'],
                        'module_chapter_id' => $moduleChapterId,
                        'chapter_number' => $chapterNumber,
                        'verse_number' => $verseNumber,
                        'raw_text' => $rawVerse,
                    ];
                }

                DB::table('module_chapters')->where('id', $moduleChapterId)->update([
                    'verses_count' => count($verses),
                    'updated_at' => $now,
                ]);
            }

            if (count($verseRows) >= $chunkSize) {
                $imported += $this->flushVerseRows($translation, $verseRows, $textRows);
                $verseRows = [];
                $textRows = [];
            }
        }

        if ($verseRows !== []) {
            $imported += $this->flushVerseRows($translation, $verseRows, $textRows);
        }

        return $imported;
    }

    /**
     * @param  array{id: int, module_id: int, code: string}  $translation
     * @param  array<string, array{id: int, chapters: array<int, int>}>  $moduleBooks
     * @param  list<array<string, mixed>>  $books
     */
    private function importSqliteVerses(\PDO $pdo, array $translation, array $moduleBooks, array $books): int
    {
        $now = now();
        $chunkSize = max(100, (int) $this->option('chunk'));
        $canonicalChapters = DB::table('canonical_chapters')
            ->get(['id', 'canonical_book_id', 'number'])
            ->mapWithKeys(fn ($chapter) => ["{$chapter->canonical_book_id}:{$chapter->number}" => (int) $chapter->id])
            ->all();
        $verseRows = [];
        $textRows = [];
        $imported = 0;
        $statement = $pdo->prepare('SELECT chapter, verse, text FROM verses WHERE book_number = :book_number ORDER BY chapter, verse');

        foreach ($books as $book) {
            $moduleBook = $moduleBooks[$book['path_name']] ?? null;

            if (! $moduleBook) {
                continue;
            }

            $statement->execute(['book_number' => (int) $book['sqlite_book_number']]);
            $chapterCounts = [];

            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $chapterNumber = (int) $row['chapter'];
                $verseNumber = (int) $row['verse'];
                $canonicalChapterId = $canonicalChapters["{$book['canonical_book_id']}:{$chapterNumber}"] ?? null;
                $moduleChapterId = $moduleBook['chapters'][$chapterNumber] ?? null;

                if (! $canonicalChapterId || ! $moduleChapterId || $verseNumber < 1) {
                    continue;
                }

                $verseRows[] = [
                    'canonical_book_id' => $book['canonical_book_id'],
                    'canonical_chapter_id' => $canonicalChapterId,
                    'chapter_number' => $chapterNumber,
                    'verse_number' => $verseNumber,
                    'osis_ref' => $book['osis_code'] ? "{$book['osis_code']}.{$chapterNumber}.{$verseNumber}" : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $textRows[] = [
                    'book' => $book,
                    'module_book_id' => $moduleBook['id'],
                    'module_chapter_id' => $moduleChapterId,
                    'chapter_number' => $chapterNumber,
                    'verse_number' => $verseNumber,
                    'raw_text' => (string) $row['text'],
                ];
                $chapterCounts[$moduleChapterId] = ($chapterCounts[$moduleChapterId] ?? 0) + 1;

                if (count($verseRows) >= $chunkSize) {
                    $imported += $this->flushVerseRows($translation, $verseRows, $textRows);
                    $verseRows = [];
                    $textRows = [];
                }
            }

            foreach ($chapterCounts as $moduleChapterId => $count) {
                DB::table('module_chapters')->where('id', $moduleChapterId)->update([
                    'verses_count' => $count,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($verseRows !== []) {
            $imported += $this->flushVerseRows($translation, $verseRows, $textRows);
        }

        return $imported;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseBookHtml(string $html): array
    {
        $chapters = [];
        $parts = preg_split('/<h4[^>]*>\s*(\d+)\s*<\/h4>/iu', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (! is_array($parts) || count($parts) < 3) {
            return $chapters;
        }

        for ($i = 1; $i < count($parts); $i += 2) {
            $chapterNumber = (int) $parts[$i];
            $chapterHtml = (string) ($parts[$i + 1] ?? '');
            $chapters[$chapterNumber] = [];

            if (preg_match_all('/<p\b[^>]*>(.*?)(?=<p\b|<h4\b|<\/body>|<\/html>|$)/isu', $chapterHtml, $matches)) {
                foreach ($matches[1] as $paragraph) {
                    $verse = $this->parseVerseParagraph($paragraph);

                    if ($verse) {
                        $chapters[$chapterNumber][$verse['number']] = $verse['raw'];
                    }
                }
            }
        }

        return $chapters;
    }

    /**
     * @return array{number: int, raw: string}|null
     */
    private function parseVerseParagraph(string $paragraph): ?array
    {
        $paragraph = trim($paragraph);

        if ($paragraph === '') {
            return null;
        }

        if (preg_match('/^\s*<sup[^>]*>\s*(\d+)\s*<\/sup>\s*(.*)$/isu', $paragraph, $matches)) {
            return ['number' => (int) $matches[1], 'raw' => trim($matches[2])];
        }

        $plainStart = trim(html_entity_decode(strip_tags($paragraph), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (preg_match('/^(\d+)\s+(.+)$/us', $plainStart, $matches)) {
            $number = (int) $matches[1];
            $raw = preg_replace('/^\s*\d+\s+/u', '', $paragraph) ?? $paragraph;

            return ['number' => $number, 'raw' => trim($raw)];
        }

        return null;
    }

    /**
     * @param  array{id: int, module_id: int, code: string}  $translation
     * @param  list<array<string, mixed>>  $verseRows
     * @param  list<array<string, mixed>>  $textRows
     */
    private function flushVerseRows(array $translation, array $verseRows, array $textRows): int
    {
        $now = now();

        DB::table('verses')->upsert(
            $verseRows,
            ['canonical_book_id', 'chapter_number', 'verse_number'],
            ['canonical_chapter_id', 'osis_ref', 'updated_at'],
        );

        $bookIds = array_values(array_unique(array_column($verseRows, 'canonical_book_id')));
        $verseIds = DB::table('verses')
            ->whereIn('canonical_book_id', $bookIds)
            ->get(['id', 'canonical_book_id', 'chapter_number', 'verse_number'])
            ->mapWithKeys(fn ($verse) => ["{$verse->canonical_book_id}:{$verse->chapter_number}:{$verse->verse_number}" => (int) $verse->id])
            ->all();
        $rows = [];

        foreach ($textRows as $row) {
            $book = $row['book'];
            $verseId = $verseIds["{$book['canonical_book_id']}:{$row['chapter_number']}:{$row['verse_number']}"] ?? null;

            if (! $verseId) {
                continue;
            }

            $normalized = $this->normalizeVerseText((string) $row['raw_text']);
            $rows[] = [
                'verse_id' => $verseId,
                'translation_id' => $translation['id'],
                'module_book_id' => $row['module_book_id'],
                'module_chapter_id' => $row['module_chapter_id'],
                'legacy_verse_id' => null,
                'text' => $normalized['text'],
                'text_plain' => $normalized['plain'],
                'text_raw' => $row['raw_text'],
                'has_strong_markup' => $normalized['has_strong'],
                'metadata_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('verse_texts')->upsert(
            $rows,
            ['translation_id', 'verse_id'],
            ['module_book_id', 'module_chapter_id', 'legacy_verse_id', 'text', 'text_plain', 'text_raw', 'has_strong_markup', 'metadata_json', 'updated_at'],
        );

        $this->replaceStrongTokens($translation['id'], $rows);

        return count($rows);
    }

    /**
     * @return array{text: string, plain: string, has_strong: bool}
     */
    private function normalizeVerseText(string $rawText): array
    {
        $text = $this->normalizeReaderHtml($rawText);
        $hasStrong = preg_match('/<S\b[^>]*>\s*[GH]?\d{1,5}\s*<\/S>|\b[HG]\d{1,5}\b/iu', $text) === 1;
        $plainSource = preg_replace('/<S\b[^>]*>\s*[GH]?\d{1,5}\s*<\/S>/iu', '', $text) ?? $text;
        $plainSource = preg_replace('/\s*\b[HG]\d{1,5}\b/u', '', $plainSource) ?? $plainSource;
        $plain = html_entity_decode(strip_tags($plainSource), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s{2,}/u', ' ', trim($plain)) ?? trim($plain);

        return [
            'text' => $text,
            'plain' => $plain,
            'has_strong' => $hasStrong,
        ];
    }

    private function normalizeReaderHtml(string $rawText): string
    {
        $text = html_entity_decode($rawText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*br\s*\/?\s*>/iu', ' ', $text) ?? $text;
        $text = preg_replace('/<\s*pb\s*\/?\s*>/iu', '', $text) ?? $text;
        $text = preg_replace('/<\/?\s*(?:J|FI|FO|FR)\b[^>]*>/iu', '', $text) ?? $text;
        $text = preg_replace('/<\s*s\b[^>]*>\s*([GH]?\d{1,5})\s*<\s*\/\s*s\s*>/iu', '<S>$1</S>', $text) ?? $text;
        $text = preg_replace('/(?<![>\p{L}\p{N}])([GH]\d{1,5})(?![\p{L}\p{N}<])/u', '<S>$1</S>', $text) ?? $text;
        $text = strip_tags($text, '<S><i><b><em><strong>');
        $text = preg_replace('/\s+([,.;:!?])/u', '$1', $text) ?? $text;
        $text = preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);

        return $text;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function replaceStrongTokens(int $translationId, array $rows): void
    {
        $strongRows = array_values(array_filter($rows, fn (array $row): bool => (bool) $row['has_strong_markup']));

        if ($strongRows === []) {
            return;
        }

        $verseIds = array_column($strongRows, 'verse_id');
        $verseTexts = DB::table('verse_texts')
            ->where('translation_id', $translationId)
            ->whereIn('verse_id', $verseIds)
            ->get(['id', 'verse_id', 'text_raw'])
            ->keyBy('verse_id');
        $strongEntryIds = DB::table('strong_entries')->pluck('id', 'number')->all();
        $insertRows = [];
        $now = now();

        DB::table('verse_strong_tokens')
            ->whereIn('verse_text_id', $verseTexts->pluck('id')->all())
            ->delete();

        foreach ($verseTexts as $verseText) {
            $order = 0;

            foreach ($this->extractStrongTokens((string) $verseText->text_raw) as $token) {
                $insertRows[] = [
                    'verse_text_id' => (int) $verseText->id,
                    'verse_id' => (int) $verseText->verse_id,
                    'strong_entry_id' => $strongEntryIds[$token['number']] ?? null,
                    'strong_number' => $token['number'],
                    'token_order' => ++$order,
                    'surface_text' => $token['surface'],
                    'grammar_code' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($insertRows, 1000) as $chunk) {
            DB::table('verse_strong_tokens')->insert($chunk);
        }
    }

    /**
     * @return list<array{number: string, surface: string|null}>
     */
    private function extractStrongTokens(string $rawText): array
    {
        $tokens = [];
        $surface = null;
        $text = $this->normalizeReaderHtml($rawText);
        $parts = preg_split('/(<S>\s*[GH]?\d{1,5}\s*<\/S>)|\s+/iu', trim($text), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) ?: [];

        foreach ($parts as $part) {
            if (preg_match_all('/(?:<S>\s*)?([GH]?\d{1,5})(?:\s*<\/S>)?/iu', $part, $matches) && preg_match('/<S\b|^[HG]\d{1,5}$/iu', trim($part))) {
                foreach ($matches[0] as $number) {
                    $tokens[] = ['number' => strip_tags($number), 'surface' => $surface];
                }

                continue;
            }

            $cleanSurface = trim(strip_tags($part), " \t\n\r\0\x0B.,;:!?()[]{}\"'");

            if ($cleanSurface !== '' && ! preg_match('/^\d+$/u', $cleanSurface)) {
                $surface = $cleanSurface;
            }
        }

        return $tokens;
    }
}
