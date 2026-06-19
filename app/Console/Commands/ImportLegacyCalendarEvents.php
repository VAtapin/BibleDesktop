<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportLegacyCalendarEvents extends Command
{
    protected $signature = 'calendar:legacy:import-events {--path=OLD/MemoryDays.xml}';

    protected $description = 'Import legacy Orthodox calendar events from MemoryDays.xml.';

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

        $now = now();
        $types = [];
        $events = [];

        foreach ($xml->event as $event) {
            $legacyType = (int) $event->type;
            $typeCode = "legacy_{$legacyType}";
            $types[$legacyType] = [
                'code' => $typeCode,
                'name' => "Legacy type {$legacyType}",
                'description' => 'Imported from OLD/MemoryDays.xml.',
                'sort_order' => $legacyType,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk(array_values($types), 500) as $chunk) {
            DB::table('calendar_event_types')->upsert(
                $chunk,
                ['code'],
                ['name', 'description', 'sort_order', 'updated_at'],
            );
        }

        $typeIds = DB::table('calendar_event_types')->pluck('id', 'code')->all();

        foreach ($xml->event as $event) {
            $startMonth = (int) $event->s_month;
            $startDay = (int) $event->s_date;
            $endMonth = (int) $event->f_month;
            $endDay = (int) $event->f_date;
            $legacyType = (int) $event->type;
            $dateRuleType = $this->dateRuleType($startMonth, $startDay, $endMonth, $endDay);

            $events[] = [
                'calendar_event_type_id' => $typeIds["legacy_{$legacyType}"] ?? null,
                'name' => trim((string) $event->name),
                'legacy_type' => $legacyType,
                'date_rule_type' => $dateRuleType,
                'start_month' => $startMonth > 0 ? $startMonth : null,
                'start_day' => $startMonth > 0 ? $startDay : null,
                'start_offset' => $startMonth === 0 ? $startDay : null,
                'end_month' => $endMonth > 0 ? $endMonth : null,
                'end_day' => $endMonth > 0 ? $endDay : null,
                'end_offset' => $endMonth === 0 ? $endDay : null,
                'metadata_json' => json_encode([
                    'source' => 'OLD/MemoryDays.xml',
                    'raw' => [
                        's_month' => $startMonth,
                        's_date' => $startDay,
                        'f_month' => $endMonth,
                        'f_date' => $endDay,
                        'type' => $legacyType,
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($events): void {
            DB::table('calendar_events')
                ->whereNotNull('legacy_type')
                ->orderBy('id')
                ->chunkById(500, function ($legacyEvents): void {
                    $legacyEventIds = $legacyEvents->pluck('id')->all();

                    DB::table('calendar_day_events')
                        ->whereIn('calendar_event_id', $legacyEventIds)
                        ->delete();
                    DB::table('calendar_events')
                        ->whereIn('id', $legacyEventIds)
                        ->delete();
                });

            foreach (array_chunk($events, 500) as $chunk) {
                DB::table('calendar_events')->insert($chunk);
            }
        });

        $this->components->info(sprintf(
            'Imported calendar events: %d event types, %d events.',
            count($types),
            count($events),
        ));

        return self::SUCCESS;
    }

    private function dateRuleType(int $startMonth, int $startDay, int $endMonth, int $endDay): string
    {
        $isRange = $startMonth !== $endMonth || $startDay !== $endDay;

        if ($startMonth === 0) {
            return $isRange ? 'pascha_relative_range' : 'pascha_relative';
        }

        return $isRange ? 'fixed_range' : 'fixed';
    }
}
