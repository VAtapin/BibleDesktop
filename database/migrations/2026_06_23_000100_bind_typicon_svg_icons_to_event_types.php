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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('calendar_event_types')) {
            return;
        }

        foreach ($this->iconMap() as $legacyType => $icon) {
            DB::table('calendar_event_types')
                ->where('legacy_type', $legacyType)
                ->update([
                    'typicon_symbol' => $icon,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('calendar_event_types')) {
            return;
        }

        foreach (array_keys($this->iconMap()) as $legacyType) {
            DB::table('calendar_event_types')
                ->where('legacy_type', $legacyType)
                ->update([
                    'typicon_symbol' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function iconMap(): array
    {
        return [
            0 => '1',
            1 => '1',
            2 => '1',
            3 => '2',
            4 => '3',
            5 => '4',
            6 => '5',
        ];
    }
};
