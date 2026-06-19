<?php

namespace App\Filament\Resources\BibleModules\Pages;

use App\Filament\Resources\BibleModules\BibleModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBibleModules extends ManageRecords
{
    protected static string $resource = BibleModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
