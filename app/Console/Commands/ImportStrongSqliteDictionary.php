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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportStrongSqliteDictionary extends Command
{
    protected $signature = 'bible:strong:import-sqlite
        {--path=OLD/Mod/BibleQuote_7.5.0.900/Library/System/Strong/Лексикон.dictionary.SQLite3}
        {--chunk=500 : Database upsert chunk size}';

    protected $description = 'Import a BibleQuote SQLite Strong dictionary into the global Strong lexicon tables.';

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $path = is_file($path) ? $path : base_path($path);

        if (! is_file($path)) {
            $this->error("Strong SQLite dictionary not found: {$path}");

            return self::FAILURE;
        }

        $pdo = new PDO('sqlite:'.$path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (! $this->sqliteHasTables($pdo, ['dictionary', 'info'])) {
            $this->error('SQLite file is not a BibleQuote Strong dictionary.');

            return self::FAILURE;
        }

        $info = $pdo->query('SELECT name, value FROM info')?->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        $now = now();
        $code = 'STRONG_RU';

        DB::table('strong_lexicons')->updateOrInsert(
            ['code' => $code],
            [
                'name' => (string) ($info['description'] ?? 'Русский лексикон Strong'),
                'language' => (string) ($info['articles_language'] ?? $info['language'] ?? 'ru'),
                'copyright' => null,
                'comment' => (string) ($info['detailed_info'] ?? ''),
                'metadata_json' => json_encode([
                    'source' => basename($path),
                    'type' => $info['type'] ?? null,
                    'is_strong' => $info['is_strong'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $lexiconId = (int) DB::table('strong_lexicons')->where('code', $code)->value('id');
        $statement = $pdo->query('SELECT topic, definition, lexeme, transliteration, pronunciation, short_definition FROM dictionary ORDER BY topic');
        $chunkSize = max(100, (int) $this->option('chunk'));
        $rows = [];
        $imported = 0;

        while ($row = $statement?->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'strong_lexicon_id' => $lexiconId,
                'number' => (string) $row['topic'],
                'word' => $row['lexeme'] ?: null,
                'transliteration' => $row['transliteration'] ?: null,
                'pronunciation' => $row['pronunciation'] ?: null,
                'content' => $row['definition'] ?: $row['short_definition'],
                'raw_content' => $row['definition'] ?: null,
                'metadata_json' => json_encode([
                    'short_definition' => $row['short_definition'] ?: null,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= $chunkSize) {
                $this->upsertRows($rows);
                $imported += count($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $this->upsertRows($rows);
            $imported += count($rows);
        }

        $this->components->info("Imported {$imported} Strong entries from ".basename($path).'.');

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertRows(array $rows): void
    {
        DB::table('strong_entries')->upsert(
            $rows,
            ['number'],
            ['strong_lexicon_id', 'word', 'transliteration', 'pronunciation', 'content', 'raw_content', 'metadata_json', 'updated_at'],
        );
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
}
