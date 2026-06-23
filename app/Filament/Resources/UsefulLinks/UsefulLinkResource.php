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

namespace App\Filament\Resources\UsefulLinks;

use App\Filament\Resources\UsefulLinks\Pages\ManageUsefulLinks;
use App\Models\UsefulLink;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
use UnitEnum;

class UsefulLinkResource extends Resource
{
    protected static ?string $model = UsefulLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Полезные материалы';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->label('Slug')->required()->maxLength(120),
            TextInput::make('title')->label('Название')->required()->maxLength(220),
            Textarea::make('description')->label('Описание')->rows(4)->columnSpanFull(),
            TextInput::make('url')->label('URL')->url()->required()->maxLength(500)->columnSpanFull(),
            FileUpload::make('cover_image_url')
                ->label('Картинка')
                ->image()
                ->disk('public')
                ->directory('useful-links')
                ->visibility('public')
                ->imageResizeMode('cover')
                ->imageResizeTargetWidth('640')
                ->imageResizeTargetHeight('360')
                ->maxSize(4096)
                ->columnSpanFull(),
            Select::make('category')->label('Категория')->options([
                'monastery' => 'Монастырь',
                'app' => 'Приложение',
                'project' => 'Проект',
                'service' => 'Сервис',
                'media' => 'Медиа',
            ])->required()->default('project'),
            TextInput::make('icon')->label('Иконка')->maxLength(80),
            TextInput::make('sort_order')->label('Порядок')->numeric()->required()->default(0),
            Toggle::make('is_public')->label('Показывать')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('category')->label('Категория')->sortable(),
                TextColumn::make('url')->label('URL')->limit(60),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                IconColumn::make('is_public')->label('Показывать')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsefulLinks::route('/')];
    }
}
