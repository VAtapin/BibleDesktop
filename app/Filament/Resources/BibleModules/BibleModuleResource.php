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

namespace App\Filament\Resources\BibleModules;

use App\Filament\Resources\BibleModules\Pages\ManageBibleModules;
use App\Models\BibleModule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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

class BibleModuleResource extends Resource
{
    protected static ?string $model = BibleModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Библия';

    protected static ?string $navigationLabel = 'Модули';

    protected static ?string $modelLabel = 'модуль';

    protected static ?string $pluralModelLabel = 'модули';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('language_id')
                    ->label('Язык')
                    ->relationship('language', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->label('Тип')
                    ->options([
                        'bible' => 'Библия',
                        'commentary' => 'Комментарий',
                        'dictionary' => 'Словарь',
                        'calendar' => 'Календарь',
                        'other' => 'Другое',
                    ])
                    ->required(),
                TextInput::make('code')
                    ->label('Код')
                    ->required()
                    ->maxLength(40),
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(240),
                TextInput::make('short_name')
                    ->label('Короткое название')
                    ->maxLength(80),
                TextInput::make('version')
                    ->label('Версия')
                    ->maxLength(40),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                FileUpload::make('cover_path')
                    ->label('Обложка')
                    ->image()
                    ->disk('public')
                    ->directory('module-covers')
                    ->visibility('public')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('3:4')
                    ->imageResizeTargetWidth('480')
                    ->imageResizeTargetHeight('640')
                    ->maxSize(4096),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->required()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                Toggle::make('is_public')
                    ->label('Публичный')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->sortable(),
                TextColumn::make('language.code')
                    ->label('Язык')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                IconColumn::make('is_public')
                    ->label('Публичный')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBibleModules::route('/'),
        ];
    }
}
