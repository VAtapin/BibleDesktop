<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrthodoxCalendarService
{
    /**
     * @return array{
     *     date: string,
     *     pascha_date: string,
     *     events: \Illuminate\Support\Collection<int, array{id: int, name: string, legacy_type: int|null, date_rule_type: string, is_fasting: bool}>,
     *     fasting_events: \Illuminate\Support\Collection<int, array{id: int, name: string, legacy_type: int|null, date_rule_type: string, is_fasting: bool}>,
     *     readings: \Illuminate\Support\Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, date_rule_type: string}>
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
            'pascha_date' => $pascha->toDateString(),
            'events' => $events,
            'fasting_events' => $events
                ->filter(fn (array $event): bool => $event['is_fasting'])
                ->values(),
            'readings' => $readings,
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
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, legacy_type: int|null, date_rule_type: string, is_fasting: bool}>
     */
    private function eventsForDay(CarbonImmutable $day, CarbonImmutable $pascha): Collection
    {
        return DB::table('calendar_events')
            ->orderBy('legacy_type')
            ->orderBy('name')
            ->get(['id', 'name', 'legacy_type', 'date_rule_type', 'start_month', 'start_day', 'start_offset', 'end_month', 'end_day', 'end_offset'])
            ->filter(fn ($event): bool => $this->matchesDay($event, $day, $pascha))
            ->values()
            ->map(fn ($event) => [
                'id' => (int) $event->id,
                'name' => (string) $event->name,
                'legacy_type' => $event->legacy_type === null ? null : (int) $event->legacy_type,
                'date_rule_type' => (string) $event->date_rule_type,
                'is_fasting' => (int) $event->legacy_type === 10,
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, date_rule_type: string}>
     */
    private function readingsForDay(CarbonImmutable $day, CarbonImmutable $pascha): Collection
    {
        return DB::table('calendar_readings')
            ->orderBy('sort_order')
            ->orderBy('reading_type')
            ->orderBy('passage_ref')
            ->get(['id', 'reading_type', 'title', 'passage_ref', 'date_rule_type', 'month', 'day', 'offset'])
            ->filter(fn ($reading): bool => $this->readingMatchesDay($reading, $day, $pascha))
            ->values()
            ->map(fn ($reading) => [
                'id' => (int) $reading->id,
                'type' => (string) $reading->reading_type,
                'title' => $reading->title === null ? null : (string) $reading->title,
                'passage_ref' => (string) $reading->passage_ref,
                'date_rule_type' => (string) $reading->date_rule_type,
            ]);
    }

    private function readingMatchesDay(object $reading, CarbonImmutable $day, CarbonImmutable $pascha): bool
    {
        return match ($reading->date_rule_type) {
            'fixed' => (int) $reading->month === $day->month && (int) $reading->day === $day->day,
            'pascha_relative' => $pascha->addDays((int) $reading->offset)->isSameDay($day),
            default => false,
        };
    }

    private function matchesDay(object $event, CarbonImmutable $day, CarbonImmutable $pascha): bool
    {
        return match ($event->date_rule_type) {
            'fixed' => (int) $event->start_month === $day->month && (int) $event->start_day === $day->day,
            'pascha_relative' => $pascha->addDays((int) $event->start_offset)->isSameDay($day),
            'fixed_range' => $this->isBetweenInclusive(
                $day,
                CarbonImmutable::create($day->year, (int) $event->start_month, (int) $event->start_day, 0, 0, 0, 'UTC'),
                CarbonImmutable::create($day->year, (int) $event->end_month, (int) $event->end_day, 0, 0, 0, 'UTC'),
            ),
            'pascha_relative_range' => $this->isBetweenInclusive(
                $day,
                $pascha->addDays((int) $event->start_offset),
                $pascha->addDays((int) $event->end_offset),
            ),
            default => false,
        };
    }

    private function isBetweenInclusive(CarbonImmutable $day, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        if ($end->lessThan($start)) {
            $end = $end->addYear();
        }

        return $day->betweenIncluded($start, $end);
    }
}
