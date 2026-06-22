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

namespace App\Filament\Resources\Recipes;

use App\Filament\Resources\Recipes\Pages\ManageRecipes;
use App\Models\Recipe;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Рецепты';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('recipe_category_id')->label('Категория')->relationship('category', 'name')->searchable()->preload()->required(),
            Select::make('user_id')->label('Автор')->relationship('author', 'name')->searchable()->preload(),
            TextInput::make('title')->label('Название')->required()->maxLength(220)->columnSpanFull(),
            Textarea::make('summary')->label('Кратко')->columnSpanFull(),
            Textarea::make('ingredients')->label('Ингредиенты')->rows(6)->columnSpanFull(),
            TextInput::make('cover_image_url')->label('Картинка')->maxLength(500)->columnSpanFull(),
            TextInput::make('youtube_url')->label('YouTube')->maxLength(500)->columnSpanFull(),
            Select::make('fasting_rule')->label('Тип поста')->options([
                'dry' => 'Сухоядение',
                'no_oil' => 'Горячее без масла',
                'oil' => 'С маслом',
                'fish' => 'Рыба',
            ]),
            Select::make('status')->label('Статус')->options([
                'pending' => 'На модерации',
                'approved' => 'Опубликован',
                'rejected' => 'Отклонён',
            ])->required()->default('pending'),
            TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0),
            Toggle::make('is_public')->label('Показывать')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Категория')->sortable(),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('fasting_rule')->label('Пост'),
                IconColumn::make('is_public')->label('Показывать')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRecipes::route('/')];
    }
}
