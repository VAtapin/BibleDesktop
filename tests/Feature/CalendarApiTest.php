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
        <s_month>6</s_month>
        <s_date>9</s_date>
        <f_month>6</f_month>
        <f_date>9</f_date>
        <name>Прп. Кирилла, игумена Белоезерского (1427)</name>
        <type>5</type>
    </event>
    <event>
        <s_month>0</s_month>
        <s_date>-48</s_date>
        <f_month>0</f_month>
        <f_date>-1</f_date>
        <name>Великий пост</name>
        <type>10</type>
    </event>
    <event>
        <s_month>0</s_month>
        <s_date>71</s_date>
        <f_month>0</f_month>
        <f_date>71</f_date>
        <name>Рим.9:18-33</name>
        <type>204</type>
    </event>
</MemoryDays>
XML);

        $this->artisan('calendar:legacy:import-events', ['--path' => 'storage/app/calendar-test.xml'])
            ->assertSuccessful();

        @unlink($path);

        $this->assertSame(4, DB::table('calendar_events')->count());
        $this->assertDatabaseHas('calendar_event_types', [
            'legacy_type' => 1,
            'name' => 'Двунадесятые праздники',
            'is_visible' => true,
        ]);
        $this->assertDatabaseHas('calendar_event_types', [
            'legacy_type' => 5,
            'name' => 'Малые славословные праздники',
            'is_visible' => false,
        ]);

        $this->getJson('/api/calendar/day?date=2026-01-19')
            ->assertOk()
            ->assertJsonPath('data.date', '2026-01-19')
            ->assertJsonPath('data.events.0.name', 'Святое Богоявление')
            ->assertJsonPath('data.events.0.type.name', 'Двунадесятые праздники')
            ->assertJsonPath('data.events.0.type.typicon_symbol', '☦');

        $this->getJson('/api/calendar/day?date=2026-06-22')
            ->assertOk()
            ->assertJsonPath('data.old_style_date', '2026-06-09')
            ->assertJsonCount(0, 'data.events');

        DB::table('calendar_event_types')
            ->where('legacy_type', 5)
            ->update(['is_visible' => true]);

        $this->getJson('/api/calendar/day?date=2026-06-22')
            ->assertOk()
            ->assertJsonPath('data.events.0.name', 'Прп. Кирилла, игумена Белоезерского (1427)');

        $this->getJson('/api/calendar/day?date=2026-04-12')
            ->assertOk()
            ->assertJsonPath('data.pascha_date', '2026-04-12')
            ->assertJsonPath('data.events.0.name', 'Светлое Христово Воскресение. Пасха');

        $this->getJson('/api/calendar/day?date=2026-03-01')
            ->assertOk()
            ->assertJsonCount(0, 'data.fasting_events');

        DB::table('calendar_event_types')
            ->where('legacy_type', 10)
            ->update(['is_visible' => true]);

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

    public function test_legacy_reading_import_keeps_apostle_gospel_and_psalter(): void
    {
        $path = storage_path('app/calendar-readings-test.xml');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<MemoryDays>
    <event>
        <s_month>0</s_month>
        <s_date>79</s_date>
        <f_month>0</f_month>
        <f_date>79</f_date>
        <name>Рим.14:9-18</name>
        <type>204</type>
    </event>
    <event>
        <s_month>0</s_month>
        <s_date>79</s_date>
        <f_month>0</f_month>
        <f_date>79</f_date>
        <name>Мф.12:14-16:22-30</name>
        <type>207</type>
    </event>
    <event>
        <s_month>0</s_month>
        <s_date>79</s_date>
        <f_month>0</f_month>
        <f_date>79</f_date>
        <name>Пс.46-54; Пс.55-63</name>
        <type>302</type>
    </event>
</MemoryDays>
XML);

        $this->artisan('calendar:legacy:import-readings', [
            '--path' => 'storage/app/calendar-readings-test.xml',
            '--truncate' => true,
        ])->assertSuccessful();

        @unlink($path);

        $this->getJson('/api/calendar/day?date=2026-06-30')
            ->assertOk()
            ->assertJsonCount(3, 'data.readings')
            ->assertJsonPath('data.readings.0.type', 'apostle')
            ->assertJsonPath('data.readings.0.passage_ref', 'Rom.14.9-18')
            ->assertJsonPath('data.readings.1.type', 'gospel')
            ->assertJsonPath('data.readings.1.passage_ref', 'Matt.12.14-16; Matt.12.22-30')
            ->assertJsonPath('data.readings.2.type', 'psalm')
            ->assertJsonPath('data.readings.2.passage_ref', 'Ps.46.1-54.999; Ps.55.1-63.999');
    }
}
