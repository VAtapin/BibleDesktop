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

namespace App\Filament\Resources\MonasteryServices;

use App\Filament\Resources\MonasteryServices\Pages\ManageMonasteryServices;
use App\Models\MonasteryService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MonasteryServiceResource extends Resource
{
    protected static ?string $model = MonasteryService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Богослужения';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')->label('Начало')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('location')->label('Место')->limit(45),
                TextColumn::make('source_url')->label('Источник')->limit(35),
                TextColumn::make('imported_at')->label('Импорт')->dateTime('d.m.Y H:i')->sortable(),
                IconColumn::make('is_public')->label('Показывать')->boolean(),
            ])
            ->defaultSort('starts_at')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMonasteryServices::route('/')];
    }
}
