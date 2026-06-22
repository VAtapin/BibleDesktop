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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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
            TextInput::make('servings')->label('Базовое число порций')->numeric()->required()->default(4),
            FileUpload::make('cover_image_url')
                ->label('Картинка')
                ->image()
                ->disk('public')
                ->directory('recipe-covers')
                ->visibility('public')
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('4:3')
                ->imageResizeTargetWidth('960')
                ->imageResizeTargetHeight('720')
                ->maxSize(4096)
                ->columnSpanFull(),
            Repeater::make('ingredientItems')
                ->label('Ингредиенты')
                ->relationship()
                ->schema([
                    TextInput::make('name')->label('Ингредиент')->required()->maxLength(220)->columnSpan(4),
                    TextInput::make('amount')->label('Количество')->numeric()->columnSpan(2),
                    TextInput::make('unit')->label('Ед.')->maxLength(40)->columnSpan(2),
                    TextInput::make('note')->label('Примечание')->maxLength(255)->columnSpan(3),
                    TextInput::make('sort_order')->label('Порядок')->numeric()->default(0)->columnSpan(1),
                ])
                ->columns(12)
                ->defaultItems(0)
                ->addActionLabel('Добавить ингредиент')
                ->reorderableWithButtons()
                ->columnSpanFull(),
            Textarea::make('ingredients')
                ->label('Ингредиенты текстом, если нужно')
                ->helperText('Резервное поле для старых или свободных рецептов. Для пересчёта порций используйте список выше.')
                ->rows(4)
                ->columnSpanFull(),
            Repeater::make('steps')
                ->label('Шаги приготовления')
                ->relationship()
                ->schema([
                    TextInput::make('step_number')->label('Шаг')->numeric()->required()->columnSpan(2),
                    Textarea::make('body')->label('Текст')->required()->columnSpan(7),
                    FileUpload::make('image_url')
                        ->label('Картинка шага')
                        ->image()
                        ->disk('public')
                        ->directory('recipe-steps')
                        ->visibility('public')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('4:3')
                        ->imageResizeTargetWidth('960')
                        ->imageResizeTargetHeight('720')
                        ->maxSize(4096)
                        ->columnSpan(3),
                ])
                ->columns(12)
                ->defaultItems(0)
                ->addActionLabel('Добавить шаг')
                ->reorderableWithButtons()
                ->columnSpanFull(),
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
