<?php

namespace App\Support;

use PDO;
use ZipArchive;

class ModuleImportInspector
{
    /**
     * @param  list<string>  $allowedLanguages
     * @return array<string, mixed>
     */
    public function inspect(string $path, array $allowedLanguages = ['ru', 'de', 'en', 'uk']): array
    {
        if (! is_file($path)) {
            return $this->result($path, 'unknown', false, ['Файл не найден.']);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'zip' => $this->inspectZip($path, $allowedLanguages),
            'sqlite', 'sqlite3' => $this->inspectSqlite($path, $allowedLanguages),
            default => $this->result($path, 'unknown', false, ['Поддерживаются только ZIP и SQLite3.']),
        };
    }

    /**
     * @param  list<string>  $allowedLanguages
     * @return array<string, mixed>
     */
    private function inspectZip(string $path, array $allowedLanguages): array
    {
        if (str_starts_with(basename($path), 'BibleQuote_')) {
            return $this->result($path, 'zip', false, ['Это архив программы BibleQuote, а не отдельный модуль.']);
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return $this->result($path, 'zip', false, ['Не удалось открыть ZIP.']);
        }

        try {
            $iniPath = $this->findIniPath($zip);

            if (! $iniPath) {
                return $this->result($path, 'zip', false, ['bibleqt.ini не найден.']);
            }

            $ini = $this->parseIni($this->readZipText($zip, $iniPath));
            $meta = $ini['meta'];
            $language = $this->languageCode($path, $meta);
            $books = $ini['books'];
            $missing = [];

            foreach ($books as $book) {
                $bookPath = trim(dirname($iniPath).'/'.($book['PathName'] ?? ''), '/');

                if (($book['PathName'] ?? '') !== '' && ! $this->findZipEntry($zip, $bookPath)) {
                    $missing[] = (string) $book['PathName'];
                }
            }

            $errors = [];

            if (($meta['Bible'] ?? 'N') !== 'Y') {
                $errors[] = 'Это не Bible-модуль.';
            }

            if (! $this->languageAllowed($language, $allowedLanguages)) {
                $errors[] = "Язык {$language} сейчас не включён для импорта.";
            }

            if ($books === []) {
                $errors[] = 'В bibleqt.ini не найдены книги.';
            }

            if ($missing !== []) {
                $errors[] = 'Не найдены файлы книг: '.implode(', ', array_slice($missing, 0, 8));
            }

            return $this->result($path, 'zip', $errors === [], $errors, [
                'format' => 'BibleQuote ZIP',
                'name' => $meta['BibleName'] ?? $meta['ModuleName'] ?? pathinfo($path, PATHINFO_FILENAME),
                'short_name' => $meta['BibleShortName'] ?? null,
                'language' => $language,
                'books' => count($books),
                'verses' => null,
                'has_strong' => ($meta['StrongNumbers'] ?? 'N') === 'Y',
                'encoding' => $meta['DefaultEncoding'] ?? 'UTF-8',
            ]);
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  list<string>  $allowedLanguages
     * @return array<string, mixed>
     */
    private function inspectSqlite(string $path, array $allowedLanguages): array
    {
        try {
            $pdo = new PDO('sqlite:'.$path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\Throwable $e) {
            return $this->result($path, 'sqlite', false, ['Не удалось открыть SQLite: '.$e->getMessage()]);
        }

        if (! $this->sqliteHasTables($pdo, ['books', 'info', 'verses'])) {
            return $this->result($path, 'sqlite', false, ['Это не MyBible SQLite-модуль с books/info/verses.']);
        }

        $meta = $pdo->query('SELECT name, value FROM info')?->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        $language = (string) ($meta['language'] ?? $this->languageCode($path, []));
        $books = (int) $pdo->query('SELECT COUNT(*) FROM books')?->fetchColumn();
        $verses = (int) $pdo->query('SELECT COUNT(*) FROM verses')?->fetchColumn();
        $errors = [];

        if (! $this->languageAllowed($language, $allowedLanguages)) {
            $errors[] = "Язык {$language} сейчас не включён для импорта.";
        }

        if ($books < 1 || $verses < 1) {
            $errors[] = 'В модуле нет книг или стихов.';
        }

        return $this->result($path, 'sqlite', $errors === [], $errors, [
            'format' => 'MyBible SQLite',
            'name' => $meta['description'] ?? pathinfo($path, PATHINFO_FILENAME),
            'short_name' => pathinfo($path, PATHINFO_FILENAME),
            'language' => $language,
            'books' => $books,
            'verses' => $verses,
            'has_strong' => ((string) ($meta['strong_numbers'] ?? 'false')) === 'true',
            'encoding' => 'UTF-8',
        ]);
    }

    /**
     * @param  list<string>  $errors
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function result(string $path, string $type, bool $importable, array $errors = [], array $extra = []): array
    {
        return array_merge([
            'path' => $path,
            'file' => basename($path),
            'type' => $type,
            'importable' => $importable,
            'errors' => $errors,
        ], $extra);
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

    private function readZipText(ZipArchive $zip, string $path): string
    {
        $content = $zip->getFromName($path);

        if ($content === false) {
            return '';
        }

        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        return mb_convert_encoding($content, 'UTF-8', ['Windows-1251', 'Windows-1252', 'ISO-8859-1']);
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

            if ($line === '' || str_starts_with($line, ';') || str_starts_with($line, '//') || ! str_contains($line, '=')) {
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

    private function findZipEntry(ZipArchive $zip, string $path): ?string
    {
        $path = strtolower(trim(str_replace('\\', '/', $path), '/'));

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (strtolower(trim(str_replace('\\', '/', $name), '/')) === $path) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $tables
     */
    private function sqliteHasTables(PDO $pdo, array $tables): bool
    {
        $existing = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")
            ?->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $existing = array_map('strtolower', array_map('strval', $existing));

        foreach ($tables as $table) {
            if (! in_array(strtolower($table), $existing, true)) {
                return false;
            }
        }

        return true;
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
            str_contains($name, 'English') || str_contains($name, '0En') || ($meta['language'] ?? null) === 'en' => 'en',
            default => (string) ($meta['language'] ?? 'en'),
        };
    }

    /**
     * @param  list<string>  $allowedLanguages
     */
    private function languageAllowed(string $languageCode, array $allowedLanguages): bool
    {
        $allowedLanguages = array_filter(array_map(
            fn (string $language): string => strtolower(trim($language)),
            $allowedLanguages,
        ));

        return $allowedLanguages === [] || in_array(strtolower($languageCode), $allowedLanguages, true);
    }
}
