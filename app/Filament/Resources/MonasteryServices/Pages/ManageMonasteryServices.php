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
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMonasteryServices extends ManageRecords
{
    protected static string $resource = MonasteryServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Создать')];
    }
}
