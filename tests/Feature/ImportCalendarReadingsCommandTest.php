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

class ImportCalendarReadingsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_calendar_readings_from_csv(): void
    {
        $path = storage_path('app/calendar-readings-test.csv');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'CSV'
date_rule_type,month,day,offset,reading_type,title,passage_ref,sort_order
fixed,1,6,,gospel,Богоявление,Mark.1.9-11,10
pascha_relative,,,-1,apostle,Великая суббота,Rom.6.3-11,20
CSV);

        try {
            $this->artisan('calendar:import-readings', [
                '--path' => 'storage/app/calendar-readings-test.csv',
            ])
                ->expectsOutputToContain('Imported calendar readings: 2 rows.')
                ->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('calendar_readings', [
            'date_rule_type' => 'fixed',
            'month' => 1,
            'day' => 6,
            'offset' => null,
            'reading_type' => 'gospel',
            'title' => 'Богоявление',
            'passage_ref' => 'Mark.1.9-11',
            'sort_order' => 10,
        ]);
        $this->assertDatabaseHas('calendar_readings', [
            'date_rule_type' => 'pascha_relative',
            'month' => null,
            'day' => null,
            'offset' => -1,
            'reading_type' => 'apostle',
            'title' => 'Великая суббота',
            'passage_ref' => 'Rom.6.3-11',
            'sort_order' => 20,
        ]);

        $this->getJson('/api/calendar/day?date=2026-04-11')
            ->assertOk()
            ->assertJsonPath('data.readings.0.type', 'apostle')
            ->assertJsonPath('data.readings.0.passage_ref', 'Rom.6.3-11');

        $this->assertSame(2, DB::table('calendar_readings')->count());
    }

    public function test_it_imports_legacy_memory_days_readings_from_xml(): void
    {
        $path = storage_path('app/memory-days-readings-test.xml');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<MemoryDays>
    <event>
        <s_month>0</s_month>
        <s_date>71</s_date>
        <f_month>0</f_month>
        <f_date>71</f_date>
        <name>Рим.9:18-33</name>
        <type>204</type>
    </event>
    <event>
        <s_month>0</s_month>
        <s_date>71</s_date>
        <f_month>0</f_month>
        <f_date>71</f_date>
        <name>Мф.11:2-15</name>
        <type>207</type>
    </event>
</MemoryDays>
XML);

        try {
            $this->artisan('calendar:legacy:import-readings', [
                '--path' => 'storage/app/memory-days-readings-test.xml',
            ])
                ->expectsOutputToContain('Imported legacy calendar readings: 2 rows')
                ->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('calendar_readings', [
            'date_rule_type' => 'pascha_relative',
            'offset' => 71,
            'reading_type' => 'apostle',
            'title' => 'На литургии: Апостол',
            'passage_ref' => 'Rom.9.18-33',
        ]);
        $this->assertDatabaseHas('calendar_readings', [
            'date_rule_type' => 'pascha_relative',
            'offset' => 71,
            'reading_type' => 'gospel',
            'title' => 'На литургии: Евангелие',
            'passage_ref' => 'Matt.11.2-15',
        ]);

        $this->getJson('/api/calendar/day?date=2026-06-22')
            ->assertOk()
            ->assertJsonPath('data.liturgical_period', 'Седмица 4-я по Пятидесятнице')
            ->assertJsonPath('data.readings.0.passage_ref', 'Rom.9.18-33')
            ->assertJsonPath('data.readings.1.passage_ref', 'Matt.11.2-15');
    }
}
