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

namespace App\Filament\Resources\LegacyBooks\Pages;

use App\Filament\Resources\LegacyBooks\LegacyBookResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLegacyBooks extends ManageRecords
{
    protected static string $resource = LegacyBookResource::class;
}
