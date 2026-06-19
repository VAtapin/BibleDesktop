<?php

namespace App\Filament\Resources\LegacyBooks\Pages;

use App\Filament\Resources\LegacyBooks\LegacyBookResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLegacyBooks extends ManageRecords
{
    protected static string $resource = LegacyBookResource::class;
}
