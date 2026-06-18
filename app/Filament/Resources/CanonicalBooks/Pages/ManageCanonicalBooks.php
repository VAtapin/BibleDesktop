<?php

namespace App\Filament\Resources\CanonicalBooks\Pages;

use App\Filament\Resources\CanonicalBooks\CanonicalBookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCanonicalBooks extends ManageRecords
{
    protected static string $resource = CanonicalBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
