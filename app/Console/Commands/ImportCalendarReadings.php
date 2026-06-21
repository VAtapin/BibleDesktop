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
use RuntimeException;

class ImportCalendarReadings extends Command
{
    protected $signature = 'calendar:import-readings
        {--path=storage/app/calendar-readings.csv}
        {--truncate : Delete existing calendar_readings before import}';

    protected $description = 'Import fixed and Pascha-relative daily readings from a UTF-8 CSV file.';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        if (! is_file($path)) {
            $this->error("Calendar readings CSV not found: {$path}");

            return self::FAILURE;
        }

        if (! DB::getSchemaBuilder()->hasTable('calendar_readings')) {
            $this->error('Table calendar_readings is missing. Run migrations first.');

            return self::FAILURE;
        }

        if ((bool) $this->option('truncate')) {
            DB::table('calendar_readings')->delete();
        }

        try {
            $rows = $this->readRows($path);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $imported = 0;

        foreach ($rows as $row) {
            DB::table('calendar_readings')->updateOrInsert(
                [
                    'date_rule_type' => $row['date_rule_type'],
                    'month' => $row['month'],
                    'day' => $row['day'],
                    'offset' => $row['offset'],
                    'reading_type' => $row['reading_type'],
                    'passage_ref' => $row['passage_ref'],
                ],
                [
                    'title' => $row['title'],
                    'sort_order' => $row['sort_order'],
                    'metadata_json' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            $imported++;
        }

        $this->components->info("Imported calendar readings: {$imported} rows.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{date_rule_type: string, month: int|null, day: int|null, offset: int|null, reading_type: string, title: string|null, passage_ref: string, sort_order: int}>
     */
    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException("Unable to open calendar readings CSV: {$path}");
        }

        try {
            $header = fgetcsv($handle);

            if (! is_array($header)) {
                throw new RuntimeException('Calendar readings CSV is empty.');
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/u', '', (string) $header[0]) ?? (string) $header[0];
            $header = array_map(fn ($column): string => trim((string) $column), $header);
            $required = ['date_rule_type', 'month', 'day', 'offset', 'reading_type', 'title', 'passage_ref', 'sort_order'];
            $missing = array_values(array_diff($required, $header));

            if ($missing !== []) {
                throw new RuntimeException('Calendar readings CSV missing columns: '.implode(', ', $missing));
            }

            $rows = [];
            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if ($this->isBlankRow($values)) {
                    continue;
                }

                $row = array_combine($header, array_pad($values, count($header), ''));

                if (! is_array($row)) {
                    throw new RuntimeException("Invalid CSV row at line {$line}.");
                }

                $rows[] = $this->normalizeRow($row, $line);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function isBlankRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array{date_rule_type: string, month: int|null, day: int|null, offset: int|null, reading_type: string, title: string|null, passage_ref: string, sort_order: int}
     */
    private function normalizeRow(array $row, int $line): array
    {
        $dateRuleType = trim((string) $row['date_rule_type']);
        $readingType = trim((string) $row['reading_type']);
        $passageRef = trim((string) $row['passage_ref']);

        if (! in_array($dateRuleType, ['fixed', 'pascha_relative'], true)) {
            throw new RuntimeException("Invalid date_rule_type at line {$line}: {$dateRuleType}");
        }

        if (! in_array($readingType, ['gospel', 'apostle'], true)) {
            throw new RuntimeException("Invalid reading_type at line {$line}: {$readingType}");
        }

        if ($passageRef === '') {
            throw new RuntimeException("Missing passage_ref at line {$line}.");
        }

        $month = $this->nullableInt($row['month'] ?? null);
        $day = $this->nullableInt($row['day'] ?? null);
        $offset = $this->nullableInt($row['offset'] ?? null);

        if ($dateRuleType === 'fixed' && (! $month || ! $day)) {
            throw new RuntimeException("Fixed reading requires month and day at line {$line}.");
        }

        if ($dateRuleType === 'pascha_relative' && $offset === null) {
            throw new RuntimeException("Pascha-relative reading requires offset at line {$line}.");
        }

        return [
            'date_rule_type' => $dateRuleType,
            'month' => $dateRuleType === 'fixed' ? $month : null,
            'day' => $dateRuleType === 'fixed' ? $day : null,
            'offset' => $dateRuleType === 'pascha_relative' ? $offset : null,
            'reading_type' => $readingType,
            'title' => trim((string) $row['title']) ?: null,
            'passage_ref' => $passageRef,
            'sort_order' => $this->nullableInt($row['sort_order'] ?? null) ?? 0,
        ];
    }

    private function nullableInt(?string $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }
}
