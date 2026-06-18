<?php

namespace App\Filament\Resources\Canons\Pages;

use App\Filament\Resources\Canons\CanonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCanons extends ManageRecords
{
    protected static string $resource = CanonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
