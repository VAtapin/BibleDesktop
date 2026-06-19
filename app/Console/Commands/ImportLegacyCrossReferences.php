<?php

namespace App\Console\Commands;

use App\Support\LegacySqlDump;
use App\Support\TskReferenceParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyCrossReferences extends Command
{
    protected $signature = 'bible:legacy:import-cross-references
        {--path=OLD/bible-desktop.sql}
        {--chunk=500 : Legacy quote chunk size}';

    protected $description = 'Import legacy quote.tsk references into cross_references.';

    public function handle(TskReferenceParser $parser): int
    {
        $path = base_path((string) $this->option('path'));
        $chunkSize = max(100, (int) $this->option('chunk'));

        if (! is_file($path)) {
            $this->error("Legacy SQL dump not found: {$path}");

            return self::FAILURE;
        }

        $bookIds = DB::table('canonical_books')->pluck('id', 'slug')->all();
        $verseIds = DB::table('verses')
            ->get(['id', 'canonical_book_id', 'chapter_number', 'verse_number'])
            ->mapWithKeys(fn ($verse) => ["{$verse->canonical_book_id}:{$verse->chapter_number}:{$verse->verse_number}" => (int) $verse->id])
            ->all();

        if ($bookIds === [] || $verseIds === []) {
            $this->error('Canonical books or verses are missing. Run seeders and bible:legacy:import-verses first.');

            return self::FAILURE;
        }

        $reader = new LegacySqlDump($path);
        $rows = [];
        $seen = [];
        $scanned = 0;
        $upserted = 0;
        $skippedSources = 0;
        $skippedReferences = 0;
        $skippedTargets = 0;

        DB::transaction(function () use (
            $reader,
            $parser,
            $bookIds,
            $verseIds,
            $chunkSize,
            &$rows,
            &$seen,
            &$scanned,
            &$upserted,
            &$skippedSources,
            &$skippedReferences,
            &$skippedTargets,
        ): void {
            DB::table('cross_references')->where('source', 'legacy_quote')->delete();

            foreach ($reader->rows('quote') as $row) {
                $rows[] = $row;

                if (count($rows) < $chunkSize) {
                    continue;
                }

                $result = $this->importQuoteChunk($rows, $parser, $bookIds, $verseIds, $seen);
                $scanned += $result['scanned'];
                $upserted += $result['inserted'];
                $skippedSources += $result['skipped_sources'];
                $skippedReferences += $result['skipped_references'];
                $skippedTargets += $result['skipped_targets'];
                $rows = [];
            }

            if ($rows !== []) {
                $result = $this->importQuoteChunk($rows, $parser, $bookIds, $verseIds, $seen);
                $scanned += $result['scanned'];
                $upserted += $result['inserted'];
                $skippedSources += $result['skipped_sources'];
                $skippedReferences += $result['skipped_references'];
                $skippedTargets += $result['skipped_targets'];
            }
        });

        $stored = DB::table('cross_references')->where('source', 'legacy_quote')->count();

        $this->components->info(sprintf(
            'Imported cross references: %d quotes scanned, %d links stored, %d link candidates, %d source verses skipped, %d references skipped, %d targets skipped.',
            $scanned,
            $stored,
            $upserted,
            $skippedSources,
            $skippedReferences,
            $skippedTargets,
        ));

        return self::SUCCESS;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, int> $bookIds
     * @param array<string, int> $verseIds
     * @param array<string, bool> $seen
     * @return array{scanned: int, inserted: int, skipped_sources: int, skipped_references: int, skipped_targets: int}
     */
    private function importQuoteChunk(array $rows, TskReferenceParser $parser, array $bookIds, array $verseIds, array &$seen): array
    {
        $now = now();
        $crossReferenceRows = [];
        $seen = [];
        $scanned = 0;
        $skippedSources = 0;
        $skippedReferences = 0;
        $skippedTargets = 0;

        foreach ($rows as $row) {
            $scanned++;
            $sourceBookId = $bookIds[(string) $row['shortName']] ?? null;
            $sourceVerseId = $sourceBookId
                ? ($verseIds["{$sourceBookId}:{$row['chapterNr']}:{$row['verseNr']}"] ?? null)
                : null;

            if (! $sourceVerseId) {
                $skippedSources++;
                continue;
            }

            $parsed = $parser->parseList((string) $row['tsk']);
            $skippedReferences += $parsed['skipped'];

            foreach ($parsed['references'] as $reference) {
                $targetBookId = $bookIds[$reference['book_slug']] ?? null;

                if (! $targetBookId) {
                    $skippedTargets++;
                    continue;
                }

                for ($verse = $reference['verse_start']; $verse <= $reference['verse_end']; $verse++) {
                    $targetVerseId = $verseIds["{$targetBookId}:{$reference['chapter']}:{$verse}"] ?? null;

                    if (! $targetVerseId) {
                        $skippedTargets++;
                        continue;
                    }

                    $key = "{$sourceVerseId}:{$targetVerseId}:tsk";

                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $crossReferenceRows[] = [
                        'source_verse_id' => $sourceVerseId,
                        'target_verse_id' => $targetVerseId,
                        'type' => 'tsk',
                        'source' => 'legacy_quote',
                        'metadata_json' => json_encode([
                            'legacy_quote_id' => (int) $row['id'],
                            'raw_ref' => $reference['raw'],
                        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        $insertChunkSize = DB::connection()->getDriverName() === 'sqlite' ? 100 : 1000;

        foreach (array_chunk($crossReferenceRows, $insertChunkSize) as $chunk) {
            DB::table('cross_references')->insertOrIgnore($chunk);
        }

        return [
            'scanned' => $scanned,
            'inserted' => count($crossReferenceRows),
            'skipped_sources' => $skippedSources,
            'skipped_references' => $skippedReferences,
            'skipped_targets' => $skippedTargets,
        ];
    }
}
