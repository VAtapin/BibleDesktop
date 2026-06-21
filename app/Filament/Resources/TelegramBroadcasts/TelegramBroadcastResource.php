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

namespace App\Filament\Resources\TelegramBroadcasts;

use App\Filament\Resources\TelegramBroadcasts\Pages\ManageTelegramBroadcasts;
use App\Models\TelegramBroadcast;
use App\Services\Telegram\TelegramAdminService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TelegramBroadcastResource extends Resource
{
    protected static ?string $model = TelegramBroadcast::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Telegram';

    protected static ?string $navigationLabel = 'Рассылки';

    protected static ?string $modelLabel = 'рассылка';

    protected static ?string $pluralModelLabel = 'рассылки';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('created_by')
                    ->default(fn (): ?int => auth()->id()),
                TextInput::make('title')
                    ->required()
                    ->maxLength(160),
                Textarea::make('body')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('draft')
                    ->maxLength(30),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sent_count')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('send')
                    ->label('Send')
                    ->requiresConfirmation()
                    ->visible(fn (TelegramBroadcast $record): bool => $record->status !== 'sent')
                    ->action(function (TelegramBroadcast $record): void {
                        app(TelegramAdminService::class)->sendBroadcast($record);
                    }),
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
            'index' => ManageTelegramBroadcasts::route('/'),
        ];
    }
}
