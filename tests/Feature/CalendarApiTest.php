<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_calendar_import_and_day_endpoint(): void
    {
        $path = storage_path('app/calendar-test.xml');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<MemoryDays>
    <event>
        <s_month>0</s_month>
        <s_date>0</s_date>
        <f_month>0</f_month>
        <f_date>0</f_date>
        <name>Светлое Христово Воскресение. Пасха</name>
        <type>0</type>
    </event>
    <event>
        <s_month>1</s_month>
        <s_date>6</s_date>
        <f_month>1</f_month>
        <f_date>6</f_date>
        <name>Святое Богоявление</name>
        <type>1</type>
    </event>
    <event>
        <s_month>0</s_month>
        <s_date>-48</s_date>
        <f_month>0</f_month>
        <f_date>-1</f_date>
        <name>Великий пост</name>
        <type>10</type>
    </event>
</MemoryDays>
XML);

        $this->artisan('calendar:legacy:import-events', ['--path' => 'storage/app/calendar-test.xml'])
            ->assertSuccessful();

        @unlink($path);

        $this->assertSame(3, DB::table('calendar_events')->count());

        $this->getJson('/api/calendar/day?date=2026-01-06')
            ->assertOk()
            ->assertJsonPath('data.date', '2026-01-06')
            ->assertJsonPath('data.events.0.name', 'Святое Богоявление');

        $this->getJson('/api/calendar/day?date=2026-04-12')
            ->assertOk()
            ->assertJsonPath('data.pascha_date', '2026-04-12')
            ->assertJsonPath('data.events.0.name', 'Светлое Христово Воскресение. Пасха');

        $this->getJson('/api/calendar/day?date=2026-03-01')
            ->assertOk()
            ->assertJsonPath('data.fasting_events.0.name', 'Великий пост')
            ->assertJsonPath('data.fasting_events.0.is_fasting', true);
    }

    public function test_day_endpoint_returns_calendar_readings(): void
    {
        DB::table('calendar_readings')->insert([
            'date_rule_type' => 'fixed',
            'month' => 1,
            'day' => 6,
            'reading_type' => 'gospel',
            'title' => 'Навечерие Богоявления',
            'passage_ref' => 'Mark.1.9-11',
            'sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('calendar_readings')->insert([
            'date_rule_type' => 'pascha_relative',
            'offset' => 0,
            'reading_type' => 'apostle',
            'title' => 'Пасха',
            'passage_ref' => 'Acts.1.1-8',
            'sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/calendar/day?date=2026-01-06')
            ->assertOk()
            ->assertJsonPath('data.readings.0.type', 'gospel')
            ->assertJsonPath('data.readings.0.title', 'Навечерие Богоявления')
            ->assertJsonPath('data.readings.0.passage_ref', 'Mark.1.9-11');

        $this->getJson('/api/calendar/day?date=2026-04-12')
            ->assertOk()
            ->assertJsonPath('data.readings.0.type', 'apostle')
            ->assertJsonPath('data.readings.0.passage_ref', 'Acts.1.1-8');
    }
}
