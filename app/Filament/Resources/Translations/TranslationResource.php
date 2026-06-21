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

namespace App\Filament\Resources\Translations;

use App\Filament\Resources\Translations\Pages\ManageTranslations;
use App\Models\Translation;
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

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Bible';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('module_id')
                    ->relationship('module', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('language_id')
                    ->relationship('language', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('canon_id')
                    ->relationship('canon', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(40),
                TextInput::make('name')
                    ->required()
                    ->maxLength(240),
                TextInput::make('short_name')
                    ->maxLength(80),
                TextInput::make('license')
                    ->maxLength(120),
                TextInput::make('source')
                    ->maxLength(240),
                Textarea::make('copyright')
                    ->columnSpanFull(),
                Toggle::make('has_old_testament'),
                Toggle::make('has_new_testament'),
                Toggle::make('has_apocrypha'),
                Toggle::make('has_strong'),
                Toggle::make('is_default'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('language.code')
                    ->label('Lang')
                    ->sortable(),
                TextColumn::make('module.code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('canon.code')
                    ->label('Canon')
                    ->sortable(),
                IconColumn::make('has_strong')
                    ->boolean(),
                IconColumn::make('is_default')
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
            'index' => ManageTranslations::route('/'),
        ];
    }
}
