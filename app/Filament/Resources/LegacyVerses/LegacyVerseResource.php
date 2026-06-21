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

namespace App\Filament\Resources\LegacyVerses;

use App\Filament\Resources\LegacyVerses\Pages\ManageLegacyVerses;
use App\Models\LegacyVerse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LegacyVerseResource extends Resource
{
    protected static ?string $model = LegacyVerse::class;

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
                TextColumn::make('legacy_chapter_id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('verse.osis_ref')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('verseText.translation.code')
                    ->label('Translation')
                    ->searchable()
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLegacyVerses::route('/'),
        ];
    }
}
