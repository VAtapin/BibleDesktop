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

namespace App\Filament\Resources\Quizzes;

use App\Filament\Resources\Quizzes\Pages\ManageQuizzes;
use App\Models\Quiz;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Тесты';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Тест')
                ->description('Название, описание и публикация теста.')
                ->schema([
                    TextInput::make('slug')->label('Slug')->required()->maxLength(120),
                    TextInput::make('title')->label('Название')->required()->maxLength(220),
                    Textarea::make('description')->label('Описание')->rows(3)->columnSpanFull(),
                    TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0),
                    Toggle::make('is_public')->label('Показывать')->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Вопросы и варианты ответов')
                ->description('Вопросы редактируются прямо внутри теста. Отдельные таблицы вопросов и ответов не нужны для обычной работы.')
                ->schema([
                    Repeater::make('questions')
                        ->label('Вопросы')
                        ->relationship()
                        ->schema([
                            Textarea::make('question')->label('Вопрос')->required()->rows(2)->columnSpanFull(),
                            Select::make('answer_type')->label('Тип ответа')->options([
                                'single' => 'Один вариант',
                                'multiple' => 'Несколько вариантов',
                                'text' => 'Текстовый ответ',
                                'yes_no' => 'Да / Нет',
                                'scale' => 'Шкала',
                            ])->required()->default('single')->columnSpan(3),
                            TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0)->columnSpan(2),
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
                                ->columnSpan(7),
                            Textarea::make('explanation')->label('Пояснение после ответа')->rows(2)->columnSpanFull(),
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
                            ])->placeholder('Нет')->columnSpan(3),
                            Select::make('recommended_prayer_id')->label('Рекомендованная молитва')->relationship('recommendedPrayer', 'title')->searchable()->preload()->columnSpan(4),
                            TextInput::make('recommended_passage_ref')->label('Рекомендованный отрывок')->placeholder('Jn.3:16')->maxLength(120)->columnSpan(3),
                            Textarea::make('recommendation_text')->label('Текст рекомендации')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->defaultItems(0)
                        ->addActionLabel('Добавить вопрос')
                        ->reorderableWithButtons()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('description')->label('Описание')->limit(80)->toggleable(),
                TextColumn::make('questions_count')->counts('questions')->label('Вопросы')->sortable(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                IconColumn::make('is_public')->label('Показывать')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageQuizzes::route('/')];
    }
}
