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

class ImportLegacyCalendarEvents extends Command
{
    protected $signature = 'calendar:legacy:import-events {--path=OLD/MemoryDays.xml}';

    protected $description = 'Import legacy Orthodox calendar events from MemoryDays.xml.';

    /**
     * @var array<int, array{code: string, name: string, symbol: string|null, color: string|null, is_fasting: bool, default_visible: bool, sort: int}>
     */
    private array $eventTypeMap = [
        0 => ['code' => 'pascha', 'name' => 'Светлое Христово Воскресение. Пасха', 'symbol' => '☦', 'color' => 'red', 'is_fasting' => false, 'default_visible' => true, 'sort' => 0],
        1 => ['code' => 'twelve_great_feast', 'name' => 'Двунадесятые праздники', 'symbol' => '☦', 'color' => 'red', 'is_fasting' => false, 'default_visible' => true, 'sort' => 10],
        2 => ['code' => 'great_feast', 'name' => 'Великие праздники', 'symbol' => '☦', 'color' => 'red', 'is_fasting' => false, 'default_visible' => false, 'sort' => 20],
        3 => ['code' => 'vigil_feast', 'name' => 'Средние бденные праздники', 'symbol' => '✚', 'color' => 'red', 'is_fasting' => false, 'default_visible' => false, 'sort' => 30],
        4 => ['code' => 'polyeleos_feast', 'name' => 'Средние полиелейные праздники', 'symbol' => '✣', 'color' => 'red', 'is_fasting' => false, 'default_visible' => false, 'sort' => 40],
        5 => ['code' => 'doxology_feast', 'name' => 'Малые славословные праздники', 'symbol' => '✢', 'color' => 'red', 'is_fasting' => false, 'default_visible' => false, 'sort' => 50],
        6 => ['code' => 'six_stichera_feast', 'name' => 'Малые шестиричные праздники', 'symbol' => '✢', 'color' => 'black', 'is_fasting' => false, 'default_visible' => false, 'sort' => 60],
        7 => ['code' => 'daily_commemoration', 'name' => 'Вседневные. Служба без знака Типикона', 'symbol' => '✶', 'color' => 'black', 'is_fasting' => false, 'default_visible' => false, 'sort' => 70],
        8 => ['code' => 'memorial_date', 'name' => 'Памятные даты', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 80],
        9 => ['code' => 'departed_commemoration', 'name' => 'Дни особого поминовения усопших', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 90],
        10 => ['code' => 'fast', 'name' => 'Посты', 'symbol' => null, 'color' => null, 'is_fasting' => true, 'default_visible' => false, 'sort' => 100],
        16 => ['code' => 'saints_synaxis', 'name' => 'Соборы святых', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 160],
        17 => ['code' => 'icon_commemoration', 'name' => 'Дни почитания икон', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 170],
        18 => ['code' => 'saint_memory', 'name' => 'Дни памяти святых', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 180],
        19 => ['code' => 'new_martyrs', 'name' => 'Новомученики и исповедники российские', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 190],
        20 => ['code' => 'no_wedding', 'name' => 'Браковенчание не совершается', 'symbol' => null, 'color' => null, 'is_fasting' => true, 'default_visible' => false, 'sort' => 200],
        100 => ['code' => 'fast_free_week', 'name' => 'Сплошные седмицы', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 300],
        999 => ['code' => 'custom', 'name' => 'Пользовательский тип данных', 'symbol' => null, 'color' => null, 'is_fasting' => false, 'default_visible' => false, 'sort' => 999],
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

        $now = now();
        $events = [];

        $this->seedEventTypes($now);

        $typeIds = DB::table('calendar_event_types')->pluck('id', 'legacy_type')->all();

        foreach ($xml->event as $event) {
            $startMonth = (int) $event->s_month;
            $startDay = (int) $event->s_date;
            $endMonth = (int) $event->f_month;
            $endDay = (int) $event->f_date;
            $legacyType = (int) $event->type;

            if ($this->isReadingType($legacyType)) {
                continue;
            }

            $dateRuleType = $this->dateRuleType($startMonth, $startDay, $endMonth, $endDay);
            $typeId = $typeIds[$legacyType] ?? $this->createCustomEventType($legacyType, $now);
            $typeIds[$legacyType] = $typeId;

            $events[] = [
                'calendar_event_type_id' => $typeId,
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

            DB::table('calendar_event_types')
                ->whereNull('legacy_type')
                ->where('code', 'like', 'legacy_%')
                ->delete();

            foreach (array_chunk($events, 500) as $chunk) {
                DB::table('calendar_events')->insert($chunk);
            }
        });

        $this->components->info(sprintf(
            'Imported calendar events: %d event types, %d events.',
            DB::table('calendar_event_types')->count(),
            count($events),
        ));

        return self::SUCCESS;
    }

    private function seedEventTypes(mixed $now): void
    {
        $rows = [];

        foreach ($this->eventTypeMap as $legacyType => $type) {
            $rows[] = [
                'code' => $type['code'],
                'legacy_type' => $legacyType,
                'name' => $type['name'],
                'typicon_symbol' => $type['symbol'],
                'color' => $type['color'],
                'is_fasting' => $type['is_fasting'],
                'is_visible' => $type['default_visible'],
                'description' => 'Тип события из MemoryDays.xml по документации Богайскова.',
                'sort_order' => $type['sort'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('calendar_event_types')->upsert(
            $rows,
            ['legacy_type'],
            ['code', 'name', 'typicon_symbol', 'color', 'is_fasting', 'description', 'sort_order', 'updated_at'],
        );
    }

    private function createCustomEventType(int $legacyType, mixed $now): int
    {
        DB::table('calendar_event_types')->updateOrInsert(
            ['legacy_type' => $legacyType],
            [
                'code' => "custom_{$legacyType}",
                'name' => "Пользовательский тип {$legacyType}",
                'typicon_symbol' => null,
                'color' => null,
                'is_fasting' => false,
                'is_visible' => false,
                'description' => 'Неизвестный тип MemoryDays.xml. Можно настроить вручную.',
                'sort_order' => $legacyType,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        return (int) DB::table('calendar_event_types')
            ->where('legacy_type', $legacyType)
            ->value('id');
    }

    private function isReadingType(int $legacyType): bool
    {
        return ($legacyType >= 201 && $legacyType <= 299)
            || ($legacyType >= 301 && $legacyType <= 309);
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
