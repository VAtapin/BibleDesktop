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

namespace App\Filament\Resources\MonasteryServices\Pages;

use App\Filament\Resources\MonasteryServices\MonasteryServiceResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class ManageMonasteryServices extends ManageRecords
{
    protected static string $resource = MonasteryServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncGoogleCalendar')
                ->label('Синхронизировать Google Calendar')
                ->icon(Heroicon::ArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Синхронизировать богослужения')
                ->modalDescription('Раздел будет полностью обновлён из Google Calendar. Ручные записи здесь не используются.')
                ->action(function (): void {
                    $exitCode = Artisan::call('calendar:import-monastery-services', [
                        '--truncate' => true,
                    ]);

                    $output = trim(Artisan::output());

                    $notification = Notification::make()
                        ->title($exitCode === 0 ? 'Богослужения обновлены из Google Calendar' : 'Ошибка синхронизации Google Calendar')
                        ->body($output ?: null);

                    ($exitCode === 0 ? $notification->success() : $notification->danger())->send();
                }),
        ];
    }
}
