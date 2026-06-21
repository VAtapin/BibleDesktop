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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class TelegramMessageResource extends Resource
{
    protected static ?string $model = TelegramMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Telegram';

    protected static ?string $navigationLabel = 'Диалоги';

    protected static ?string $modelLabel = 'диалог';

    protected static ?string $pluralModelLabel = 'диалоги';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $count = TelegramMessage::query()->where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Show one row per Telegram user: the latest message in each dialog.
     *
     * @return Builder<TelegramMessage>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('id', DB::table('telegram_messages')
                ->selectRaw('MAX(id)')
                ->whereNotNull('telegram_id')
                ->groupBy('telegram_id'));
    }

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
                    ->label('Последнее сообщение')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('telegram_username')
                    ->label('Пользователь')
                    ->searchable(),
                TextColumn::make('telegram_id')
                    ->label('Telegram ID')
                    ->searchable(),
                TextColumn::make('unread_count')
                    ->label('Новые')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->state(fn (TelegramMessage $record): int => TelegramMessage::query()
                        ->where('telegram_id', $record->telegram_id)
                        ->where('status', 'new')
                        ->count()),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->sortable(),
                TextColumn::make('body')
                    ->label('Последний текст')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('answered_at')
                    ->label('Ответ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordAction('dialog')
            ->recordActions([
                Action::make('dialog')
                    ->label('Открыть диалог')
                    ->modalHeading(fn (TelegramMessage $record): string => 'Диалог: '.($record->telegram_username ?: $record->telegram_id))
                    ->modalSubmitActionLabel('Ответить')
                    ->modalContent(function (TelegramMessage $record) {
                        $messages = TelegramMessage::query()
                            ->where('telegram_id', $record->telegram_id)
                            ->orderBy('created_at')
                            ->orderBy('id')
                            ->get();

                        TelegramMessage::query()
                            ->where('telegram_id', $record->telegram_id)
                            ->where('direction', 'inbound')
                            ->where('status', 'new')
                            ->update(['status' => 'read']);

                        return view('filament.telegram.dialog', [
                            'messages' => $messages,
                        ]);
                    })
                    ->form([
                        Textarea::make('reply')
                            ->label('Ответ')
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
