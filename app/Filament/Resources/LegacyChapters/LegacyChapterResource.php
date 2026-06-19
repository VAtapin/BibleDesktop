<?php

namespace App\Filament\Resources\LegacyChapters;

use App\Filament\Resources\LegacyChapters\Pages\ManageLegacyChapters;
use App\Models\LegacyChapter;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegacyChapterResource extends Resource
{
    protected static ?string $model = LegacyChapter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

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
                TextColumn::make('legacy_book_id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('moduleChapter.chapter_number')
                    ->label('Chapter')
                    ->sortable(),
                TextColumn::make('canonicalChapter.number')
                    ->label('Canonical')
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLegacyChapters::route('/'),
        ];
    }
}
