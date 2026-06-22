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

namespace App\Filament\Resources\CalendarEvents;

use App\Filament\Resources\CalendarEvents\Pages\ManageCalendarEvents;
use App\Models\CalendarEvent;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CalendarEventResource extends Resource
{
    protected static ?string $model = CalendarEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Календарь';

    protected static ?string $navigationLabel = 'События календаря';

    protected static ?string $modelLabel = 'событие календаря';

    protected static ?string $pluralModelLabel = 'события календаря';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('calendar_event_type_id')
                ->label('Тип')
                ->relationship('type', 'name')
                ->searchable()
                ->preload(),
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->columnSpanFull(),
            Select::make('date_rule_type')
                ->label('Правило даты')
                ->options([
                    'fixed' => 'Каждый год, фиксированная дата',
                    'fixed_year' => 'Конкретный год',
                    'fixed_range' => 'Диапазон дат',
                    'pascha_relative' => 'Относительно Пасхи',
                    'pascha_relative_range' => 'Диапазон относительно Пасхи',
                ])
                ->required()
                ->default('fixed_year'),
            TextInput::make('legacy_type')
                ->label('XML тип')
                ->numeric(),
            TextInput::make('start_year')->label('Год начала')->numeric(),
            TextInput::make('end_year')->label('Год окончания')->numeric(),
            TextInput::make('start_month')->label('Месяц начала')->numeric()->minValue(1)->maxValue(12),
            TextInput::make('start_day')->label('День начала')->numeric()->minValue(1)->maxValue(31),
            TextInput::make('start_offset')->label('Смещение от Пасхи')->numeric(),
            TextInput::make('end_month')->label('Месяц окончания')->numeric()->minValue(1)->maxValue(12),
            TextInput::make('end_day')->label('День окончания')->numeric()->minValue(1)->maxValue(31),
            TextInput::make('end_offset')->label('Конечное смещение')->numeric(),
            Textarea::make('metadata_json')
                ->label('Metadata JSON')
                ->formatStateUsing(fn (mixed $state): string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $state)
                ->dehydrateStateUsing(fn (?string $state): ?array => $state ? json_decode($state, true) : null)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->limit(80),
                TextColumn::make('type.name')->label('Тип')->sortable(),
                TextColumn::make('date_rule_type')->label('Правило')->sortable(),
                TextColumn::make('start_year')->label('Год')->sortable(),
                TextColumn::make('start_month')->label('Месяц'),
                TextColumn::make('start_day')->label('День'),
            ])
            ->defaultSort('start_month')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCalendarEvents::route('/'),
        ];
    }
}
