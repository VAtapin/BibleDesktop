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

namespace App\Filament\Resources\QuizAnswers;

use App\Filament\Resources\QuizAnswers\Pages\ManageQuizAnswers;
use App\Models\QuizAnswer;
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
use UnitEnum;

class QuizAnswerResource extends Resource
{
    protected static ?string $model = QuizAnswer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Ответы тестов';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('quiz_question_id')->label('Вопрос')->relationship('question', 'question')->searchable()->preload()->required(),
            TextInput::make('answer')->label('Ответ')->required()->maxLength(500)->columnSpanFull(),
            Toggle::make('is_correct')->label('Правильный')->default(false),
            TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question.question')->label('Вопрос')->limit(80)->searchable(),
                TextColumn::make('answer')->label('Ответ')->limit(80)->searchable(),
                IconColumn::make('is_correct')->label('Верный')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageQuizAnswers::route('/')];
    }
}
