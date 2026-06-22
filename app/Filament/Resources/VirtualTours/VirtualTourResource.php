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

namespace App\Filament\Resources\VirtualTours;

use App\Filament\Resources\VirtualTours\Pages\ManageVirtualTours;
use App\Models\VirtualTour;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class VirtualTourResource extends Resource
{
    protected static ?string $model = VirtualTour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = '360° туры';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->label('Slug')->required()->maxLength(120),
            TextInput::make('title')->label('Название')->required()->maxLength(220),
            Textarea::make('description')->label('Описание')->columnSpanFull(),
            TextInput::make('cover_image_url')->label('Обложка')->maxLength(500)->columnSpanFull(),
            TextInput::make('tour_url')->label('URL тура')->url()->required()->maxLength(500)->columnSpanFull(),
            TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0),
            Toggle::make('is_public')->label('Показывать')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('tour_url')->label('URL')->limit(80),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                IconColumn::make('is_public')->label('Показывать')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVirtualTours::route('/')];
    }
}
