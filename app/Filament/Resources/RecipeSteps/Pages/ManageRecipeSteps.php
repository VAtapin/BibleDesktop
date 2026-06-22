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

namespace App\Filament\Resources\RecipeSteps\Pages;

use App\Filament\Resources\RecipeSteps\RecipeStepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRecipeSteps extends ManageRecords
{
    protected static string $resource = RecipeStepResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
