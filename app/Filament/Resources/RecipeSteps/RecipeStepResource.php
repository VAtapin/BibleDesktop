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

namespace App\Filament\Resources\RecipeSteps;

use App\Filament\Resources\RecipeSteps\Pages\ManageRecipeSteps;
use App\Models\RecipeStep;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RecipeStepResource extends Resource
{
    protected static ?string $model = RecipeStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Шаги рецептов';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('recipe_id')->label('Рецепт')->relationship('recipe', 'title')->searchable()->preload()->required(),
            TextInput::make('step_number')->label('Шаг')->numeric()->required(),
            Textarea::make('body')->label('Текст')->required()->columnSpanFull(),
            TextInput::make('image_url')->label('Картинка шага')->maxLength(500)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recipe.title')->label('Рецепт')->searchable(),
                TextColumn::make('step_number')->label('Шаг')->sortable(),
                TextColumn::make('body')->label('Текст')->limit(80),
            ])
            ->defaultSort('step_number')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRecipeSteps::route('/')];
    }
}
