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

class ImportLegacyCalendarReadings extends Command
{
    protected $signature = 'calendar:legacy:import-readings
        {--path=OLD/MemoryDays.xml}
        {--truncate : Delete existing calendar_readings before import}';

    protected $description = 'Import daily Apostle, Gospel, and Psalter readings from legacy MemoryDays.xml.';

    /**
     * @var array<string, string>
     */
    private array $bookMap = [
        'Быт' => 'Gen',
        'Исх' => 'Exod',
        'Лев' => 'Lev',
        'Чис' => 'Num',
        'Втор' => 'Deut',
        'Нав' => 'Josh',
        'Суд' => 'Judg',
        'Руф' => 'Ruth',
        '1Цар' => '1Sam',
        '2Цар' => '2Sam',
        '3Цар' => '1Kgs',
        '4Цар' => '2Kgs',
        '1Пар' => '1Chr',
        '2Пар' => '2Chr',
        'Езд' => 'Ezra',
        'Неем' => 'Neh',
        'Есф' => 'Esth',
        'Иов' => 'Job',
        'Пс' => 'Ps',
        'Притч' => 'Prov',
        'Еккл' => 'Eccl',
        'Песн' => 'Song',
        'Ис' => 'Isa',
        'Иер' => 'Jer',
        'Плач' => 'Lam',
        'Иез' => 'Ezek',
        'Дан' => 'Dan',
        'Ос' => 'Hos',
        'Иоил' => 'Joel',
        'Ам' => 'Amos',
        'Авд' => 'Obad',
        'Ион' => 'Jonah',
        'Мих' => 'Mic',
        'Наум' => 'Nah',
        'Авв' => 'Hab',
        'Соф' => 'Zeph',
        'Агг' => 'Hag',
        'Зах' => 'Zech',
        'Мал' => 'Mal',
        'Мф' => 'Matt',
        'Мк' => 'Mark',
        'Лк' => 'Luke',
        'Ин' => 'John',
        'Деян' => 'Acts',
        'Иак' => 'Jas',
        '1Пет' => '1Pet',
        '2Пет' => '2Pet',
        '1Ин' => '1John',
        '2Ин' => '2John',
        '3Ин' => '3John',
        'Иуд' => 'Jude',
        'Рим' => 'Rom',
        '1Кор' => '1Cor',
        '2Кор' => '2Cor',
        'Гал' => 'Gal',
        'Еф' => 'Eph',
        'Флп' => 'Phil',
        'Фил' => 'Phil',
        'Кол' => 'Col',
        '1Сол' => '1Thess',
        '2Сол' => '2Thess',
        '1Фес' => '1Thess',
        '2Фес' => '2Thess',
        '1Тим' => '1Tim',
        '2Тим' => '2Tim',
        'Тит' => 'Titus',
        'Флм' => 'Phlm',
        'Евр' => 'Heb',
        'Откр' => 'Rev',
    ];

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        if (! is_file($path)) {
            $this->error("Legacy calendar XML not found: {$path}");

            return self::FAILURE;
        }

        $xml = simplexml_load_file($path);

        if (! $xml) {
            throw new RuntimeException("Unable to parse legacy calendar XML: {$path}");
        }

        if ((bool) $this->option('truncate')) {
            DB::table('calendar_readings')->delete();
        }

        $rows = [];
        $skipped = 0;
        $now = now();

        foreach ($xml->event as $event) {
            $legacyType = (int) $event->type;
            $readingType = $this->readingType($legacyType);

            if ($readingType === null) {
                continue;
            }

            $passageRef = $this->normalizePassageList(trim((string) $event->name));

            if ($passageRef === '') {
                $skipped++;

                continue;
            }

            $startMonth = (int) $event->s_month;
            $startDay = (int) $event->s_date;
            $endMonth = (int) $event->f_month;
            $endDay = (int) $event->f_date;

            if ($startMonth !== $endMonth || $startDay !== $endDay) {
                $skipped++;

                continue;
            }

            $rows[] = [
                'date_rule_type' => $startMonth === 0 ? 'pascha_relative' : 'fixed',
                'month' => $startMonth > 0 ? $startMonth : null,
                'day' => $startMonth > 0 ? $startDay : null,
                'offset' => $startMonth === 0 ? $startDay : null,
                'reading_type' => $readingType,
                'title' => $this->readingTitle($legacyType),
                'passage_ref' => $passageRef,
                'sort_order' => $legacyType,
                'metadata_json' => json_encode([
                    'source' => 'OLD/MemoryDays.xml',
                    'legacy_type' => $legacyType,
                    'raw_name' => trim((string) $event->name),
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

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
                    'metadata_json' => $row['metadata_json'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ],
            );
        }

        $this->components->info(sprintf(
            'Imported legacy calendar readings: %d rows, skipped %d rows.',
            count($rows),
            $skipped,
        ));

        return self::SUCCESS;
    }

    private function readingType(int $legacyType): ?string
    {
        if ($legacyType >= 301 && $legacyType <= 309) {
            return 'psalm';
        }

        return match ($legacyType % 10) {
            4 => 'apostle',
            7 => 'gospel',
            default => null,
        };
    }

    private function readingTitle(int $legacyType): string
    {
        return [
            202 => 'На утрени',
            204 => 'На литургии: Апостол',
            207 => 'На литургии: Евангелие',
            301 => 'Псалтирь: на 1-м часе',
            302 => 'Псалтирь: на утрени',
            303 => 'Псалтирь: на 3-м часе',
            306 => 'Псалтирь: на 6-м часе',
            308 => 'Псалтирь: на вечерне',
            309 => 'Псалтирь: на 9-м часе',
        ][$legacyType] ?? match ($legacyType % 10) {
            4 => 'Апостол',
            7 => 'Евангелие',
            default => 'Чтение',
        };
    }

    private function normalizePassageList(string $value): string
    {
        $parts = preg_split('/\s*;\s*/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $reference = $this->normalizePassage(trim($part));

            if ($reference !== null) {
                $normalized[] = $reference;
            }
        }

        return implode('; ', $normalized);
    }

    private function normalizePassage(string $value): ?string
    {
        $value = str_replace(["\u{00A0}", '–', '—'], [' ', '-', '-'], $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        if (preg_match('/^([1-3]?\p{L}+)\.?(\\d+)-(\\d+)$/u', $value, $matches)) {
            $book = $this->bookMap[$matches[1]] ?? null;

            return $book ? "{$book}.{$matches[2]}.1-{$matches[3]}.999" : null;
        }

        if (! preg_match('/^([1-3]?\p{L}+)\.?(\\d+)[:.,](\\d+)(?:-(?:(\\d+)[:.,])?(\\d+))?$/u', $value, $matches)) {
            return null;
        }

        $book = $this->bookMap[$matches[1]] ?? null;

        if (! $book) {
            return null;
        }

        $chapter = (int) $matches[2];
        $verse = (int) $matches[3];
        $endChapter = isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : null;
        $endVerse = isset($matches[5]) && $matches[5] !== '' ? (int) $matches[5] : null;

        if ($endVerse === null) {
            return "{$book}.{$chapter}.{$verse}";
        }

        if ($endChapter !== null) {
            return "{$book}.{$chapter}.{$verse}-{$endChapter}.{$endVerse}";
        }

        return "{$book}.{$chapter}.{$verse}-{$endVerse}";
    }
}
