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

namespace App\Filament\Resources\CalendarEventTypes;

use App\Filament\Resources\CalendarEventTypes\Pages\ManageCalendarEventTypes;
use App\Models\CalendarEventType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class CalendarEventTypeResource extends Resource
{
    protected static ?string $model = CalendarEventType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Календарь';

    protected static ?string $navigationLabel = 'Типы событий';

    protected static ?string $modelLabel = 'тип события';

    protected static ?string $pluralModelLabel = 'типы событий';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Код')
                    ->required()
                    ->maxLength(60),
                TextInput::make('legacy_type')
                    ->label('Тип из MemoryDays.xml')
                    ->numeric(),
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(160),
                Select::make('typicon_symbol')
                    ->label('Иконка типикона')
                    ->options(self::typiconIconOptions())
                    ->nullable()
                    ->helperText('Номер SVG-файла из public/images/typicon. Если пусто, событие выводится без знака.'),
                TextInput::make('color')
                    ->label('Цвет')
                    ->maxLength(20),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->required()
                    ->default(0),
                Toggle::make('is_fasting')
                    ->label('Пост / ограничение')
                    ->default(false),
                Toggle::make('is_visible')
                    ->label('Показывать')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legacy_type')
                    ->label('XML')
                    ->sortable(),
                TextColumn::make('typicon_symbol')
                    ->label('Иконка')
                    ->formatStateUsing(fn (?string $state): HtmlString|string => self::typiconIconPreview($state))
                    ->html(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                IconColumn::make('is_fasting')
                    ->label('Пост')
                    ->boolean(),
                IconColumn::make('is_visible')
                    ->label('Показывать')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
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
            'index' => ManageCalendarEventTypes::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function typiconIconOptions(): array
    {
        return [
            '1' => '1 - Пасха, двунадесятые и великие праздники',
            '2' => '2 - Средний бденный праздник',
            '3' => '3 - Средний полиелейный праздник',
            '4' => '4 - Малый славословный праздник',
            '5' => '5 - Малый шестиричный праздник',
        ];
    }

    private static function typiconIconPreview(?string $state): HtmlString|string
    {
        if ($state === null || trim($state) === '') {
            return '—';
        }

        $icon = trim($state);

        if (preg_match('/^[1-5]$/', $icon) === 1) {
            return new HtmlString(sprintf(
                '<img src="/images/typicon/%s.svg" alt="Типикон %s" style="width:18px;height:18px;object-fit:contain;vertical-align:middle" />',
                e($icon),
                e($icon),
            ));
        }

        return $icon;
    }
}
