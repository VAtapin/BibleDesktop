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

use App\Filament\Resources\BibleModules\BibleModuleResource;
use App\Filament\Resources\CmsPages\CmsPageResource;
use App\Filament\Resources\TelegramBroadcasts\TelegramBroadcastResource;
use App\Filament\Resources\TelegramMessages\TelegramMessageResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TelegramStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $hasMessages = Schema::hasTable('telegram_messages');
        $hasBroadcasts = Schema::hasTable('telegram_broadcasts');
        $hasUsers = Schema::hasTable('users');
        $hasModules = Schema::hasTable('modules');
        $hasPages = Schema::hasTable('cms_pages');

        return [
            Stat::make('Новые сообщения', $hasMessages ? DB::table('telegram_messages')->where('status', 'new')->count() : 0)
                ->description('Telegram: открыть диалоги')
                ->color('warning')
                ->url(TelegramMessageResource::getUrl()),
            Stat::make('Диалоги Telegram', $hasMessages ? DB::table('telegram_messages')->distinct('telegram_id')->count('telegram_id') : 0)
                ->description('Все пользователи бота с перепиской')
                ->color('info')
                ->url(TelegramMessageResource::getUrl()),
            Stat::make('Пользователи сайта', $hasUsers ? DB::table('users')->count() : 0)
                ->description('Аккаунты и Telegram-профили')
                ->url(UserResource::getUrl()),
            Stat::make('Bible-модули', $hasModules ? DB::table('modules')->where('type', 'bible')->count() : 0)
                ->description('Переводы, обложки, описание')
                ->url(BibleModuleResource::getUrl()),
            Stat::make('Страницы Footer', $hasPages ? DB::table('cms_pages')->where('menu_location', 'footer')->count() : 0)
                ->description('Информация, контакты, Impressum')
                ->url(CmsPageResource::getUrl()),
            Stat::make('Рассылки Telegram', $hasBroadcasts ? DB::table('telegram_broadcasts')->where('status', 'sent')->count() : 0)
                ->description('Создать или проверить рассылку')
                ->url(TelegramBroadcastResource::getUrl()),
        ];
    }
}
