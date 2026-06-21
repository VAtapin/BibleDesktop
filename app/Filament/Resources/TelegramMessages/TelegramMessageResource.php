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

namespace App\Filament\Resources\TelegramMessages;

use App\Filament\Resources\TelegramMessages\Pages\ManageTelegramMessages;
use App\Models\TelegramMessage;
use App\Services\Telegram\TelegramAdminService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TelegramMessageResource extends Resource
{
    protected static ?string $model = TelegramMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Telegram';

    protected static ?string $navigationLabel = 'Messages';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('telegram_id')
                    ->required()
                    ->maxLength(80),
                TextInput::make('telegram_username')
                    ->maxLength(80),
                TextInput::make('chat_id')
                    ->required()
                    ->maxLength(80),
                TextInput::make('status')
                    ->required()
                    ->maxLength(30),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('admin_reply')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('telegram_username')
                    ->searchable(),
                TextColumn::make('telegram_id')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('body')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('answered_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('reply')
                    ->label('Reply')
                    ->form([
                        Textarea::make('reply')
                            ->required()
                            ->rows(5),
                    ])
                    ->action(function (TelegramMessage $record, array $data): void {
                        app(TelegramAdminService::class)->reply($record, trim((string) $data['reply']));
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
            'index' => ManageTelegramMessages::route('/'),
        ];
    }
}
