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

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportMonasteryServices extends Command
{
    protected $signature = 'calendar:import-monastery-services
        {--url= : Public or private Google Calendar ICS URL}
        {--path= : Local ICS file path, useful for offline import tests}
        {--truncate : Delete existing imported monastery services before import}';

    protected $description = 'Import monastery service schedule from a Google Calendar ICS feed.';

    public function handle(): int
    {
        $path = trim((string) $this->option('path'));
        $url = trim((string) ($this->option('url') ?: config('services.monastery_calendar.ics_url')));

        if ($path === '' && $url === '') {
            $this->error('ICS URL is not configured.');

            return self::FAILURE;
        }

        $body = $path !== ''
            ? $this->readLocalIcs($path)
            : Http::timeout(30)->get($url)->throw()->body();

        if ($body === '') {
            return self::FAILURE;
        }

        $sourceUrl = $url !== '' ? $url : $path;
        $events = $this->parseEvents($body);
        $timezone = (string) config('services.monastery_calendar.timezone', 'Europe/Berlin');
        $windowStart = CarbonImmutable::now($timezone)->subMonths(3)->startOfDay();
        $windowEnd = CarbonImmutable::now($timezone)->addMonths(18)->endOfDay();
        $rows = [];

        foreach ($events as $event) {
            array_push($rows, ...$this->eventRows($event, $sourceUrl, $timezone, $windowStart, $windowEnd));
        }

        if ((bool) $this->option('truncate')) {
            DB::table('monastery_services')->delete();
        }

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('monastery_services')->upsert(
                $chunk,
                ['external_uid'],
                ['title', 'description', 'location', 'starts_at', 'ends_at', 'is_all_day', 'source_url', 'is_public', 'imported_at', 'updated_at'],
            );
        }

        $this->info(sprintf('Imported monastery services: %d rows from %d calendar events.', count($rows), count($events)));

        return self::SUCCESS;
    }

    private function readLocalIcs(string $path): string
    {
        $resolved = realpath($path);

        if ($resolved === false || ! is_file($resolved)) {
            $this->error("ICS file not found: {$path}");

            return '';
        }

        return (string) file_get_contents($resolved);
    }

    /**
     * @return list<array<string, array{params: array<string, string>, value: string}>>
     */
    private function parseEvents(string $ics): array
    {
        $lines = $this->unfoldLines($ics);
        $events = [];
        $current = null;

        foreach ($lines as $line) {
            if ($line === 'BEGIN:VEVENT') {
                $current = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                if (is_array($current)) {
                    $events[] = $current;
                }

                $current = null;
                continue;
            }

            if (! is_array($current) || ! str_contains($line, ':')) {
                continue;
            }

            [$nameAndParams, $value] = explode(':', $line, 2);
            $parts = explode(';', $nameAndParams);
            $name = strtoupper(array_shift($parts) ?: '');
            $params = [];

            foreach ($parts as $part) {
                if (str_contains($part, '=')) {
                    [$key, $paramValue] = explode('=', $part, 2);
                    $params[strtoupper($key)] = $paramValue;
                }
            }

            if ($name === 'EXDATE') {
                $current['EXDATE_ENTRIES'] ??= [];
                $current['EXDATE_ENTRIES'][] = [
                    'params' => $params,
                    'value' => $value,
                ];

                continue;
            }

            $current[$name] = [
                'params' => $params,
                'value' => $value,
            ];
        }

        return $events;
    }

    /**
     * @return list<string>
     */
    private function unfoldLines(string $ics): array
    {
        $rawLines = preg_split("/\r\n|\n|\r/", $ics) ?: [];
        $lines = [];

        foreach ($rawLines as $line) {
            if ($line !== '' && preg_match('/^[ \t]/', $line) === 1 && $lines !== []) {
                $lines[array_key_last($lines)] .= substr($line, 1);
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @param  array<string, array{params: array<string, string>, value: string}>  $event
     * @return list<array<string, mixed>>
     */
    private function eventRows(array $event, string $sourceUrl, string $timezone, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): array
    {
        $uid = $this->value($event, 'UID');
        $summary = $this->cleanText($this->value($event, 'SUMMARY'));
        $startsAt = $this->parseDate($event['DTSTART'] ?? null, $timezone);

        if ($uid === '' || $summary === '' || ! $startsAt) {
            return [];
        }

        $endsAt = $this->parseDate($event['DTEND'] ?? null, $timezone);
        $durationSeconds = $endsAt ? max(0, $endsAt->diffInSeconds($startsAt, true)) : null;
        $description = $this->cleanText($this->value($event, 'DESCRIPTION'));
        $location = $this->cleanText($this->value($event, 'LOCATION'));
        $isAllDay = (($event['DTSTART']['params']['VALUE'] ?? '') === 'DATE') || preg_match('/^\d{8}$/', $this->value($event, 'DTSTART')) === 1;
        $occurrences = $this->occurrences($event, $startsAt, $timezone, $windowStart, $windowEnd);
        $now = now();

        return collect($occurrences)
            ->map(function (CarbonImmutable $occurrenceStart) use ($uid, $summary, $description, $location, $durationSeconds, $sourceUrl, $isAllDay, $now): array {
                $occurrenceEnd = $durationSeconds === null ? null : $occurrenceStart->addSeconds($durationSeconds);

                return [
                    'external_uid' => $uid.'|'.$occurrenceStart->format('Ymd\THis'),
                    'title' => $summary,
                    'description' => $description === '' ? null : $description,
                    'location' => $location === '' ? null : $location,
                    'starts_at' => $occurrenceStart,
                    'ends_at' => $occurrenceEnd,
                    'is_all_day' => $isAllDay,
                    'source_url' => $sourceUrl,
                    'is_public' => true,
                    'imported_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array{params: array<string, string>, value: string}>  $event
     * @return list<CarbonImmutable>
     */
    private function occurrences(array $event, CarbonImmutable $startsAt, string $timezone, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): array
    {
        $rrule = $this->parseRule($this->value($event, 'RRULE'));

        if (($rrule['FREQ'] ?? '') !== 'WEEKLY') {
            return $startsAt->betweenIncluded($windowStart, $windowEnd) ? [$startsAt] : [];
        }

        $until = isset($rrule['UNTIL'])
            ? $this->parseRuleUntil($rrule['UNTIL'], $timezone)
            : $windowEnd;
        $until = $until->min($windowEnd);
        $byDays = $this->byDays($rrule['BYDAY'] ?? '', $startsAt);
        $exDates = $this->exDates($event, $timezone);
        $cursor = $startsAt->startOfDay();
        $occurrences = [];

        while ($cursor->lessThanOrEqualTo($until)) {
            if (in_array($cursor->dayOfWeekIso, $byDays, true)) {
                $candidate = $cursor->setTime($startsAt->hour, $startsAt->minute, $startsAt->second);
                $key = $candidate->format('Y-m-d H:i:s');

                if ($candidate->betweenIncluded($windowStart, $windowEnd) && ! isset($exDates[$key])) {
                    $occurrences[] = $candidate;
                }
            }

            $cursor = $cursor->addDay();
        }

        return $occurrences;
    }

    /**
     * @return array<string, string>
     */
    private function parseRule(string $rule): array
    {
        $parts = [];

        foreach (explode(';', $rule) as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $parts[strtoupper($key)] = $value;
            }
        }

        return $parts;
    }

    private function parseRuleUntil(string $until, string $timezone): CarbonImmutable
    {
        if (str_ends_with($until, 'Z')) {
            return CarbonImmutable::createFromFormat('Ymd\THis\Z', $until, 'UTC')->setTimezone($timezone);
        }

        return CarbonImmutable::createFromFormat('Ymd\THis', $until, $timezone);
    }

    /**
     * @return list<int>
     */
    private function byDays(string $value, CarbonImmutable $fallback): array
    {
        $map = ['MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7];
        $days = collect(explode(',', $value))
            ->map(fn (string $day): ?int => $map[strtoupper(trim($day))] ?? null)
            ->filter()
            ->values()
            ->all();

        return $days === [] ? [$fallback->dayOfWeekIso] : $days;
    }

    /**
     * @param  array<string, array{params: array<string, string>, value: string}>  $event
     * @return array<string, bool>
     */
    private function exDates(array $event, string $timezone): array
    {
        $entries = $event['EXDATE_ENTRIES'] ?? [];

        if (! is_array($entries) || $entries === []) {
            return [];
        }

        $dates = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $date = $this->parseDate($entry, $timezone);

            if ($date) {
                $dates[$date->format('Y-m-d H:i:s')] = true;
            }
        }

        return $dates;
    }

    /**
     * @param  array{params: array<string, string>, value: string}|null  $property
     */
    private function parseDate(?array $property, string $timezone): ?CarbonImmutable
    {
        if (! $property) {
            return null;
        }

        $value = $property['value'];

        if (($property['params']['VALUE'] ?? '') === 'DATE' || preg_match('/^\d{8}$/', $value) === 1) {
            return CarbonImmutable::createFromFormat('Ymd', $value, $timezone)->startOfDay();
        }

        if (str_ends_with($value, 'Z')) {
            return CarbonImmutable::createFromFormat('Ymd\THis\Z', $value, 'UTC')->setTimezone($timezone);
        }

        return CarbonImmutable::createFromFormat('Ymd\THis', $value, $property['params']['TZID'] ?? $timezone);
    }

    /**
     * @param  array<string, array{params: array<string, string>, value: string}>  $event
     */
    private function value(array $event, string $name): string
    {
        return (string) ($event[$name]['value'] ?? '');
    }

    private function cleanText(string $value): string
    {
        $value = str_replace(['\\n', '\\,', '\\;'], ["\n", ',', ';'], $value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace("/[ \t]+\n/", "\n", $value) ?? $value;
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

        return trim($value);
    }
}
