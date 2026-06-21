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

namespace App\Filament\Resources\LegacyLibraries\Pages;

use App\Filament\Resources\LegacyLibraries\LegacyLibraryResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLegacyLibraries extends ManageRecords
{
    protected static string $resource = LegacyLibraryResource::class;
}
