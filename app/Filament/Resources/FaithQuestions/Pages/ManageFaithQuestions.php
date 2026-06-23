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

namespace App\Filament\Resources\FaithQuestions\Pages;

use App\Filament\Resources\FaithQuestions\FaithQuestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFaithQuestions extends ManageRecords
{
    protected static string $resource = FaithQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Создать')];
    }
}
