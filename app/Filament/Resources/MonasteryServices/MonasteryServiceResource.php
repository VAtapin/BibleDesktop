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
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class MonasteryServiceResource extends Resource
{
    protected static ?string $model = MonasteryService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Богослужения';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('external_uid')->label('UID')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('title')->label('Название')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->label('Описание')->rows(5)->columnSpanFull(),
            TextInput::make('location')->label('Место')->maxLength(500)->columnSpanFull(),
            DateTimePicker::make('starts_at')->label('Начало')->required(),
            DateTimePicker::make('ends_at')->label('Окончание'),
            Toggle::make('is_all_day')->label('Весь день'),
            Toggle::make('is_public')->label('Показывать')->default(true),
            TextInput::make('source_url')->label('Источник')->maxLength(500)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')->label('Начало')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('location')->label('Место')->limit(45),
                IconColumn::make('is_public')->label('Показывать')->boolean(),
            ])
            ->defaultSort('starts_at')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMonasteryServices::route('/')];
    }
}
