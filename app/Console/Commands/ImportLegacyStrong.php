<?php

namespace App\Console\Commands;

use App\Support\LegacySqlDump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyStrong extends Command
{
    protected $signature = 'bible:legacy:import-strong
        {--path=OLD/bible-desktop.sql}
        {--chunk=500 : Database upsert chunk size}';

    protected $description = 'Import legacy Strong lexicons and dictionary entries.';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));
        $chunkSize = max(100, (int) $this->option('chunk'));

        if (! is_file($path)) {
            $this->error("Legacy SQL dump not found: {$path}");

            return self::FAILURE;
        }

        $reader = new LegacySqlDump($path);
        $now = now();
        $legacyLexiconCodes = [];
        $lexiconRows = [];

        foreach ($reader->rows('strong_lexicons') as $row) {
            $code = $this->lexiconCode((int) $row['id'], (string) $row['language']);
            $legacyLexiconCodes[(int) $row['id']] = $code;

            $lexiconRows[] = [
                'code' => $code,
                'name' => (string) $row['name'],
                'language' => (string) $row['language'],
                'copyright' => $row['copyright'] ?: null,
                'comment' => $row['comment'] ?: null,
                'metadata_json' => json_encode(['legacy_id' => (int) $row['id']], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('strong_lexicons')->upsert(
            $lexiconRows,
            ['code'],
            ['name', 'language', 'copyright', 'comment', 'metadata_json', 'updated_at'],
        );

        $lexiconIds = DB::table('strong_lexicons')->pluck('id', 'code')->all();
        $entryRows = [];
        $importedEntries = 0;
        $skippedEntries = 0;

        foreach ($reader->rows('strong_numbers') as $row) {
            $legacyCode = (string) $row['lexiconID'];
            $lexiconCode = $legacyCode !== '' ? $legacyCode : $this->strongNumberLexiconCode((string) $row['strongNr']);
            $lexiconId = $lexiconIds[$lexiconCode] ?? null;

            if (! $lexiconId) {
                $skippedEntries++;
                continue;
            }

            $entryRows[] = [
                'strong_lexicon_id' => $lexiconId,
                'number' => (string) $row['strongNr'],
                'word' => $row['word'] ?: null,
                'transliteration' => $row['transliteration'] ?: null,
                'pronunciation' => $row['pronunciation'] ?: null,
                'content' => $row['content'] ?: null,
                'raw_content' => $row['content'] ?: null,
                'metadata_json' => json_encode(['legacy_id' => (int) $row['id_str']], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($entryRows) >= $chunkSize) {
                $this->upsertEntries($entryRows);
                $importedEntries += count($entryRows);
                $entryRows = [];
            }
        }

        if ($entryRows !== []) {
            $this->upsertEntries($entryRows);
            $importedEntries += count($entryRows);
        }

        $this->components->info(sprintf(
            'Imported Strong data: %d lexicons, %d entries, %d skipped.',
            count($lexiconRows),
            $importedEntries,
            $skippedEntries,
        ));

        return self::SUCCESS;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function upsertEntries(array $rows): void
    {
        DB::table('strong_entries')->upsert(
            $rows,
            ['number'],
            ['strong_lexicon_id', 'word', 'transliteration', 'pronunciation', 'content', 'raw_content', 'metadata_json', 'updated_at'],
        );
    }

    private function lexiconCode(int $legacyId, string $language): string
    {
        return match (mb_strtolower($language)) {
            'greek' => 'GRK',
            'hebrew' => 'HEB',
            default => $legacyId === 1 ? 'GRK' : 'HEB',
        };
    }

    private function strongNumberLexiconCode(string $number): string
    {
        return str_starts_with($number, 'G') ? 'GRK' : 'HEB';
    }
}
