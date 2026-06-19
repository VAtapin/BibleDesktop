<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyStrongTokens extends Command
{
    protected $signature = 'bible:legacy:import-strong-tokens
        {--translation=L1_RST : Translation code to scan}
        {--chunk=500 : Verse text chunk size}';

    protected $description = 'Extract Strong markers from verse_texts.text_raw into verse_strong_tokens.';

    public function handle(): int
    {
        $translationCode = (string) $this->option('translation');
        $chunkSize = max(100, (int) $this->option('chunk'));
        $translation = DB::table('translations')->where('code', $translationCode)->first(['id', 'code']);

        if (! $translation) {
            $this->error("Translation not found: {$translationCode}");

            return self::FAILURE;
        }

        $strongEntryIds = DB::table('strong_entries')->pluck('id', 'number')->all();

        if ($strongEntryIds === []) {
            $this->error('Strong dictionary is empty. Run bible:legacy:import-strong first.');

            return self::FAILURE;
        }

        $inserted = 0;
        $scanned = 0;

        $insertChunkSize = DB::connection()->getDriverName() === 'sqlite' ? 100 : 1000;

        DB::transaction(function () use ($translation, $chunkSize, $strongEntryIds, $insertChunkSize, &$inserted, &$scanned): void {
            DB::table('verse_texts')
                ->where('translation_id', $translation->id)
                ->where('has_strong_markup', true)
                ->orderBy('id')
                ->chunkById($chunkSize, function ($verseTexts) use ($strongEntryIds, $insertChunkSize, &$inserted, &$scanned): void {
                    $now = now();
                    $verseTextIds = $verseTexts->pluck('id')->all();
                    $rows = [];

                    DB::table('verse_strong_tokens')->whereIn('verse_text_id', $verseTextIds)->delete();

                    foreach ($verseTexts as $verseText) {
                        $scanned++;
                        $tokenOrder = 0;

                        foreach ($this->extractStrongTokens((string) $verseText->text_raw) as $token) {
                            $strongEntryId = $strongEntryIds[$token['number']] ?? null;

                            if (! $strongEntryId) {
                                continue;
                            }

                            $rows[] = [
                                'verse_text_id' => (int) $verseText->id,
                                'verse_id' => (int) $verseText->verse_id,
                                'strong_entry_id' => $strongEntryId,
                                'strong_number' => $token['number'],
                                'token_order' => ++$tokenOrder,
                                'surface_text' => $token['surface'],
                                'grammar_code' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    foreach (array_chunk($rows, $insertChunkSize) as $chunk) {
                        DB::table('verse_strong_tokens')->insert($chunk);
                        $inserted += count($chunk);
                    }
                });
        });

        $this->components->info(sprintf(
            'Imported Strong tokens for %s: %d verse texts scanned, %d tokens inserted.',
            $translationCode,
            $scanned,
            $inserted,
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<array{number: string, surface: string|null}>
     */
    private function extractStrongTokens(string $rawText): array
    {
        $tokens = [];
        $surface = null;
        $parts = preg_split('/\s+/u', trim($rawText), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($parts as $part) {
            if (preg_match_all('/[HG]\d{3,5}/u', $part, $matches)) {
                foreach ($matches[0] as $number) {
                    $tokens[] = [
                        'number' => $number,
                        'surface' => $surface,
                    ];
                }

                continue;
            }

            if (preg_match('/^\d+$/u', $part)) {
                continue;
            }

            $cleanSurface = trim(strip_tags($part), " \t\n\r\0\x0B.,;:!?()[]{}\"'");

            if ($cleanSurface !== '') {
                $surface = $cleanSurface;
            }
        }

        return $tokens;
    }
}
