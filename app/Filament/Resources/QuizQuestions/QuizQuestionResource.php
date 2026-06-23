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

namespace App\Filament\Resources\QuizQuestions;

use App\Filament\Resources\QuizQuestions\Pages\ManageQuizQuestions;
use App\Models\QuizQuestion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class QuizQuestionResource extends Resource
{
    protected static ?string $model = QuizQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Вопросы тестов';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('quiz_id')->label('Тест')->relationship('quiz', 'title')->searchable()->preload()->required(),
            Textarea::make('question')->label('Вопрос')->required()->columnSpanFull(),
            Select::make('answer_type')->label('Тип ответа')->options([
                'single' => 'Один вариант',
                'multiple' => 'Несколько вариантов',
                'text' => 'Текстовый ответ',
                'yes_no' => 'Да / Нет',
                'scale' => 'Шкала',
            ])->required()->default('single'),
            FileUpload::make('image_path')
                ->label('Картинка к вопросу')
                ->image()
                ->disk('public')
                ->directory('quiz-questions')
                ->visibility('public')
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth('1200')
                ->imageResizeTargetHeight('800')
                ->maxSize(4096)
                ->columnSpanFull(),
            Textarea::make('explanation')->label('Пояснение')->columnSpanFull(),
            Repeater::make('answers')
                ->label('Ответы')
                ->relationship()
                ->schema([
                    TextInput::make('answer')->label('Ответ')->required()->maxLength(500)->columnSpan(5),
                    Toggle::make('is_correct')->label('Верный')->columnSpan(1),
                    Select::make('recommendation_type')->label('Рекомендация')->options([
                        'none' => 'Нет',
                        'prayer' => 'Молитва',
                        'passage' => 'Евангелие / отрывок',
                        'text' => 'Текст',
                    ])->placeholder('Нет')->columnSpan(2),
                    Select::make('recommended_prayer_id')->label('Молитва')->relationship('recommendedPrayer', 'title')->searchable()->preload()->columnSpan(4),
                    TextInput::make('recommended_passage_ref')->label('Отрывок')->placeholder('Mt.5:1-12')->maxLength(120)->columnSpan(3),
                    Textarea::make('recommendation_text')->label('Текст рекомендации')->rows(2)->columnSpan(7),
                    TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0)->columnSpan(2),
                ])
                ->columns(12)
                ->defaultItems(0)
                ->addActionLabel('Добавить ответ')
                ->reorderableWithButtons()
                ->columnSpanFull(),
            Select::make('recommendation_type')->label('Общая рекомендация')->options([
                'none' => 'Нет',
                'prayer' => 'Молитва',
                'passage' => 'Евангелие / отрывок',
                'text' => 'Текст',
            ])->placeholder('Нет'),
            Select::make('recommended_prayer_id')->label('Рекомендованная молитва')->relationship('recommendedPrayer', 'title')->searchable()->preload(),
            TextInput::make('recommended_passage_ref')->label('Рекомендованный отрывок')->placeholder('Jn.3:16')->maxLength(120),
            Textarea::make('recommendation_text')->label('Текст рекомендации')->columnSpanFull(),
            TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quiz.title')->label('Тест')->searchable(),
                TextColumn::make('question')->label('Вопрос')->limit(90)->searchable(),
                TextColumn::make('answer_type')->label('Тип')->badge(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageQuizQuestions::route('/')];
    }
}
