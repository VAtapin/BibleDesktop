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

namespace App\Filament\Resources\FaithQuestions;

use App\Filament\Resources\FaithQuestions\Pages\ManageFaithQuestions;
use App\Models\FaithQuestion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FaithQuestionResource extends Resource
{
    protected static ?string $model = FaithQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Вопросы веры';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->label('Slug')->required()->maxLength(160),
            TextInput::make('category')->label('Раздел')->required()->default('Основы')->maxLength(120),
            TextInput::make('question')->label('Вопрос')->required()->maxLength(300)->columnSpanFull(),
            RichEditor::make('answer_html')->label('Ответ')->required()->columnSpanFull(),
            TextInput::make('source_url')->label('Источник')->url()->maxLength(500)->columnSpanFull(),
            TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0),
            Toggle::make('is_public')->label('Показывать')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')->label('Вопрос')->limit(80)->searchable()->sortable(),
                TextColumn::make('category')->label('Раздел')->searchable()->sortable(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                IconColumn::make('is_public')->label('Показывать')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFaithQuestions::route('/')];
    }
}
