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

namespace App\Filament\Resources\CanonicalBooks;

use App\Filament\Resources\CanonicalBooks\Pages\ManageCanonicalBooks;
use App\Models\CanonicalBook;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CanonicalBookResource extends Resource
{
    protected static ?string $model = CanonicalBook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('canon_id')
                    ->relationship('canon', 'name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(80),
                TextInput::make('osis_code')
                    ->maxLength(32),
                Select::make('testament')
                    ->options([
                        'old' => 'Old Testament',
                        'new' => 'New Testament',
                        'apocrypha' => 'Apocrypha',
                    ])
                    ->required(),
                TextInput::make('canonical_order')
                    ->numeric()
                    ->required(),
                TextInput::make('default_chapters_count')
                    ->numeric()
                    ->required()
                    ->default(0),
                Toggle::make('is_deuterocanonical')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('canonical_order')
                    ->sortable(),
                TextColumn::make('canon.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('osis_code')
                    ->searchable(),
                TextColumn::make('testament')
                    ->sortable(),
                TextColumn::make('default_chapters_count')
                    ->label('Chapters')
                    ->sortable(),
                IconColumn::make('is_deuterocanonical')
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
            'index' => ManageCanonicalBooks::route('/'),
        ];
    }
}
