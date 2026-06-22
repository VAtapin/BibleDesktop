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

namespace App\Filament\Resources\Prayers;

use App\Filament\Resources\Prayers\Pages\ManagePrayers;
use App\Models\Prayer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PrayerResource extends Resource
{
    protected static ?string $model = Prayer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Молитвы';

    protected static ?string $modelLabel = 'молитва';

    protected static ?string $pluralModelLabel = 'молитвы';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('language_code')
                    ->label('Язык')
                    ->options([
                        'ru' => 'Русский',
                        'cu' => 'Церковнославянский',
                    ])
                    ->required()
                    ->default('ru'),
                Select::make('category')
                    ->label('Раздел')
                    ->options([
                        'common' => 'Основные',
                        'morning' => 'Утренние',
                        'evening' => 'Вечерние',
                        'communion_before' => 'Ко Святому Причащению',
                        'communion_after' => 'После Святого Причащения',
                        'day' => 'В продолжение дня',
                        'other' => 'Другое',
                    ])
                    ->required()
                    ->default('common'),
                Select::make('liturgy_key')
                    ->label('Литургия')
                    ->options([
                        'chrysostom' => 'Иоанна Златоуста',
                        'basil' => 'Василия Великого',
                        'presanctified' => 'Преждеосвященных Даров',
                    ])
                    ->placeholder('Без привязки')
                    ->helperText('Для молитв, которые показываются только в дни соответствующей литургии.'),
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(180),
                TextInput::make('short_title')
                    ->label('Короткое название')
                    ->maxLength(80),
                RichEditor::make('intro')
                    ->label('Вступительный текст')
                    ->helperText('Короткое описание для Telegram и карточки. Полный текст храните ниже или в частях молитвы.')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->required()
                    ->default(0),
                Toggle::make('is_public')
                    ->label('Показывать')
                    ->default(true),
                RichEditor::make('body')
                    ->label('Текст молитвы')
                    ->helperText('Для короткой молитвы достаточно этого поля. Для длинных молитв добавляйте части ниже: сайт откроет их как отдельные страницы.')
                    ->required()
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike'],
                        ['h2', 'h3', 'blockquote'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull(),
                Repeater::make('sections')
                    ->label('Части / страницы молитвы')
                    ->relationship()
                    ->schema([
                        TextInput::make('title')
                            ->label('Заголовок части')
                            ->placeholder('Например: Часть 1')
                            ->maxLength(180)
                            ->columnSpan(8),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->columnSpan(4),
                        RichEditor::make('body')
                            ->label('Текст части')
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike'],
                                ['h2', 'h3', 'blockquote'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->addActionLabel('Добавить часть')
                    ->orderColumn('sort_order')
                    ->reorderableWithButtons()
                    ->columnSpanFull(),
                TextInput::make('source_url')
                    ->label('Источник')
                    ->url()
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('language_code')
                    ->label('Язык')
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Раздел')
                    ->sortable(),
                TextColumn::make('liturgy_key')
                    ->label('Литургия')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                IconColumn::make('is_public')
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
            'index' => ManagePrayers::route('/'),
        ];
    }
}
