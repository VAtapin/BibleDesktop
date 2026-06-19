<?php

namespace App\Filament\Resources\LegacyBooks;

use App\Filament\Resources\LegacyBooks\Pages\ManageLegacyBooks;
use App\Models\LegacyBook;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegacyBookResource extends Resource
{
    protected static ?string $model = LegacyBook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Migration';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legacy_id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('legacy_bible_id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('moduleBook.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('canonicalBook.slug')
                    ->searchable()
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLegacyBooks::route('/'),
        ];
    }
}
