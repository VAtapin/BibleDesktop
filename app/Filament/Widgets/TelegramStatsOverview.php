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

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TelegramStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        if (! Schema::hasTable('telegram_messages') || ! Schema::hasTable('telegram_broadcasts')) {
            return [
                Stat::make('Telegram users', 0),
                Stat::make('New messages', 0),
                Stat::make('Answered messages', 0),
                Stat::make('Broadcasts sent', 0),
            ];
        }

        return [
            Stat::make('Telegram users', DB::table('users')->whereNotNull('telegram_id')->count()),
            Stat::make('New messages', DB::table('telegram_messages')->where('status', 'new')->count()),
            Stat::make('Answered messages', DB::table('telegram_messages')->where('status', 'answered')->count()),
            Stat::make('Broadcasts sent', DB::table('telegram_broadcasts')->where('status', 'sent')->count()),
        ];
    }
}
