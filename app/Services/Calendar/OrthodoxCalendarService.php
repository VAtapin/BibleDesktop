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

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrthodoxCalendarService
{
    /**
     * @return array{
     *     date: string,
     *     old_style_date: string,
     *     pascha_date: string,
     *     liturgical_period: string|null,
     *     events: Collection<int, array{id: int, name: string, legacy_type: int|null, date_rule_type: string, is_fasting: bool, metadata: array<string, mixed>, type: array<string, mixed>|null}>,
     *     fasting_events: Collection<int, array{id: int, name: string, legacy_type: int|null, date_rule_type: string, is_fasting: bool, metadata: array<string, mixed>, type: array<string, mixed>|null}>,
     *     readings: Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, display_ref: string, date_rule_type: string}>,
     *     monastery_services: Collection<int, array{id: int, title: string, description: string|null, location: string|null, starts_at: string, ends_at: string|null, time_label: string, is_all_day: bool}>
     * }
     */
    public function day(string $date): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $pascha = $this->orthodoxPascha($day->year);
        $events = $this->eventsForDay($day, $pascha);
        $readings = $this->readingsForDay($day, $pascha);

        return [
            'date' => $day->toDateString(),
            'old_style_date' => $day->subDays(13)->toDateString(),
            'pascha_date' => $pascha->toDateString(),
            'liturgical_period' => $this->liturgicalPeriod($day, $pascha),
            'events' => $events,
            'fasting_events' => $events
                ->filter(fn (array $event): bool => $event['is_fasting'])
                ->values(),
            'readings' => $readings,
            'monastery_services' => $this->monasteryServicesForDay($day),
        ];
    }

    /**
     * Meeus Julian algorithm converted to Gregorian calendar.
     */
    public function orthodoxPascha(int $year): CarbonImmutable
    {
        $a = $year % 4;
        $b = $year % 7;
        $c = $year % 19;
        $d = (19 * $c + 15) % 30;
        $e = (2 * $a + 4 * $b - $d + 34) % 7;
        $julianDay = $d + $e + 114;
        $month = intdiv($julianDay, 31);
        $day = ($julianDay % 31) + 1;

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'UTC')->addDays(13);
    }

    /**
     * @return Collection<int, array{id: int, name: string, legacy_type: int|null, date_rule_type: string, is_fasting: bool, metadata: array<string, mixed>, type: array<string, mixed>|null}>
     */
    private function eventsForDay(CarbonImmutable $day, CarbonImmutable $pascha): Collection
    {
        if (! Schema::hasTable('calendar_events') || ! Schema::hasTable('calendar_event_types')) {
            return collect();
        }

        $hasStartYear = Schema::hasColumn('calendar_events', 'start_year');
        $hasEndYear = Schema::hasColumn('calendar_events', 'end_year');

        return DB::table('calendar_events')
            ->leftJoin('calendar_event_types', 'calendar_event_types.id', '=', 'calendar_events.calendar_event_type_id')
            ->where(fn ($query) => $query
                ->whereNull('calendar_event_types.id')
                ->orWhere('calendar_event_types.is_visible', true))
            ->orderBy('calendar_event_types.sort_order')
            ->orderBy('calendar_events.legacy_type')
            ->orderBy('calendar_events.name')
            ->get([
                'calendar_events.id',
                'calendar_events.name',
                'calendar_events.legacy_type',
                'calendar_events.date_rule_type',
                DB::raw($hasStartYear ? 'calendar_events.start_year' : 'null as start_year'),
                DB::raw($hasEndYear ? 'calendar_events.end_year' : 'null as end_year'),
                'calendar_events.start_month',
                'calendar_events.start_day',
                'calendar_events.start_offset',
                'calendar_events.end_month',
                'calendar_events.end_day',
                'calendar_events.end_offset',
                'calendar_events.metadata_json',
                'calendar_event_types.code as type_code',
                'calendar_event_types.name as type_name',
                'calendar_event_types.typicon_symbol',
                'calendar_event_types.color',
                'calendar_event_types.is_fasting',
                'calendar_event_types.sort_order as type_sort_order',
            ])
            ->filter(fn ($event): bool => $this->matchesDay($event, $day, $pascha))
            ->values()
            ->map(function ($event): array {
                $metadata = json_decode((string) ($event->metadata_json ?? ''), true);

                return [
                    'id' => (int) $event->id,
                    'name' => (string) $event->name,
                    'legacy_type' => $event->legacy_type === null ? null : (int) $event->legacy_type,
                    'date_rule_type' => (string) $event->date_rule_type,
                    'is_fasting' => (bool) $event->is_fasting || in_array((int) $event->legacy_type, [10, 20], true),
                    'metadata' => is_array($metadata) ? $metadata : [],
                    'type' => $event->type_code === null ? null : [
                        'code' => (string) $event->type_code,
                        'name' => (string) $event->type_name,
                        'typicon_symbol' => $event->typicon_symbol === null ? null : (string) $event->typicon_symbol,
                        'color' => $event->color === null ? null : (string) $event->color,
                        'sort_order' => (int) $event->type_sort_order,
                    ],
                ];
            });
    }

    /**
     * @return Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, display_ref: string, date_rule_type: string}>
     */
    private function readingsForDay(CarbonImmutable $day, CarbonImmutable $pascha): Collection
    {
        if (! Schema::hasTable('calendar_readings')) {
            return collect();
        }

        return DB::table('calendar_readings')
            ->orderBy('sort_order')
            ->orderBy('reading_type')
            ->orderBy('passage_ref')
            ->get(['id', 'reading_type', 'title', 'passage_ref', 'date_rule_type', 'month', 'day', 'offset', 'metadata_json'])
            ->filter(fn ($reading): bool => $this->readingMatchesDay($reading, $day, $pascha))
            ->values()
            ->map(function ($reading): array {
                $metadata = json_decode((string) ($reading->metadata_json ?? ''), true);

                return [
                    'id' => (int) $reading->id,
                    'type' => (string) $reading->reading_type,
                    'title' => $reading->title === null ? null : (string) $reading->title,
                    'passage_ref' => (string) $reading->passage_ref,
                    'display_ref' => is_array($metadata) && isset($metadata['raw_name'])
                        ? (string) $metadata['raw_name']
                        : (string) $reading->passage_ref,
                    'date_rule_type' => (string) $reading->date_rule_type,
                ];
            });
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: string|null, location: string|null, starts_at: string, ends_at: string|null, time_label: string, is_all_day: bool}>
     */
    private function monasteryServicesForDay(CarbonImmutable $day): Collection
    {
        if (! Schema::hasTable('monastery_services')) {
            return collect();
        }

        $timezone = (string) config('services.monastery_calendar.timezone', 'Europe/Berlin');
        $start = $day->setTimezone($timezone)->startOfDay();
        $end = $day->setTimezone($timezone)->endOfDay();

        return DB::table('monastery_services')
            ->where('is_public', true)
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->get(['id', 'title', 'description', 'location', 'starts_at', 'ends_at', 'is_all_day'])
            ->map(function ($service) use ($timezone): array {
                $startsAt = CarbonImmutable::parse((string) $service->starts_at)->setTimezone($timezone);
                $endsAt = $service->ends_at === null ? null : CarbonImmutable::parse((string) $service->ends_at)->setTimezone($timezone);
                $timeLabel = (bool) $service->is_all_day ? 'Весь день' : $startsAt->format('H:i');

                if (! (bool) $service->is_all_day && $endsAt) {
                    $timeLabel .= '-'.$endsAt->format('H:i');
                }

                return [
                    'id' => (int) $service->id,
                    'title' => (string) $service->title,
                    'description' => $service->description === null ? null : (string) $service->description,
                    'location' => $service->location === null ? null : (string) $service->location,
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt?->toIso8601String(),
                    'time_label' => $timeLabel,
                    'is_all_day' => (bool) $service->is_all_day,
                ];
            });
    }

    private function readingMatchesDay(object $reading, CarbonImmutable $day, CarbonImmutable $pascha): bool
    {
        return match ($reading->date_rule_type) {
            'fixed' => (int) $reading->month === $day->month && (int) $reading->day === $day->day,
            'pascha_relative' => $pascha->addDays((int) $reading->offset)->isSameDay($day),
            default => false,
        };
    }

    private function liturgicalPeriod(CarbonImmutable $day, CarbonImmutable $pascha): ?string
    {
        $daysAfterPascha = $pascha->diffInDays($day, false);

        if ($daysAfterPascha === 0) {
            return 'Светлое Христово Воскресение. Пасха';
        }

        if ($daysAfterPascha > 0 && $daysAfterPascha < 7) {
            return 'Светлая седмица';
        }

        if ($daysAfterPascha === 39) {
            return 'Вознесение Господне';
        }

        if ($daysAfterPascha === 49) {
            return 'День Святой Троицы. Пятидесятница';
        }

        if ($daysAfterPascha >= 50) {
            $week = intdiv($daysAfterPascha - 50, 7) + 1;

            return "Седмица {$week}-я по Пятидесятнице";
        }

        if ($daysAfterPascha < 0) {
            $week = intdiv(abs($daysAfterPascha), 7) + 1;

            return "Седмица {$week}-я перед Пасхой";
        }

        return null;
    }

    private function matchesDay(object $event, CarbonImmutable $day, CarbonImmutable $pascha): bool
    {
        $fixedDay = $this->fixedCalendarDay($event, $day);

        return match ($event->date_rule_type) {
            'fixed' => (int) $event->start_month === $fixedDay->month && (int) $event->start_day === $fixedDay->day,
            'fixed_year' => (int) $event->start_year === $day->year
                && (int) $event->start_month === $day->month
                && (int) $event->start_day === $day->day,
            'pascha_relative' => $pascha->addDays((int) $event->start_offset)->isSameDay($day),
            'fixed_range' => $this->isFixedRangeDay($event, $fixedDay),
            'pascha_relative_range' => $this->isBetweenInclusive(
                $day,
                $pascha->addDays((int) $event->start_offset),
                $pascha->addDays((int) $event->end_offset),
            ),
            default => false,
        };
    }

    private function isFixedRangeDay(object $event, CarbonImmutable $fixedDay): bool
    {
        $start = CarbonImmutable::create($fixedDay->year, (int) $event->start_month, (int) $event->start_day, 0, 0, 0, 'UTC');
        $end = CarbonImmutable::create($fixedDay->year, (int) $event->end_month, (int) $event->end_day, 0, 0, 0, 'UTC');

        if ($end->lessThan($start) && $fixedDay->lessThanOrEqualTo($end)) {
            $start = $start->subYear();
        }

        return $this->isBetweenInclusive($fixedDay, $start, $end);
    }

    private function fixedCalendarDay(object $event, CarbonImmutable $day): CarbonImmutable
    {
        if ($event->legacy_type === null) {
            return $day;
        }

        return $day->subDays(13);
    }

    private function isBetweenInclusive(CarbonImmutable $day, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        if ($end->lessThan($start)) {
            $end = $end->addYear();
        }

        return $day->betweenIncluded($start, $end);
    }
}
