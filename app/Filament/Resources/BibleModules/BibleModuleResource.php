<?php

namespace App\Filament\Resources\BibleModules;

use App\Filament\Resources\BibleModules\Pages\ManageBibleModules;
use App\Models\BibleModule;
use BackedEnum;
use UnitEnum;
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

class BibleModuleResource extends Resource
{
    protected static ?string $model = BibleModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Bible';

    protected static ?string $navigationLabel = 'Modules';

    protected static ?string $modelLabel = 'module';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('language_id')
                    ->relationship('language', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->options([
                        'bible' => 'Bible',
                        'commentary' => 'Commentary',
                        'dictionary' => 'Dictionary',
                        'calendar' => 'Calendar',
                        'other' => 'Other',
                    ])
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(40),
                TextInput::make('name')
                    ->required()
                    ->maxLength(240),
                TextInput::make('short_name')
                    ->maxLength(80),
                TextInput::make('version')
                    ->maxLength(40),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->required()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true),
                Toggle::make('is_public')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable(),
                TextColumn::make('language.code')
                    ->label('Lang')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_public')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
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
            'index' => ManageBibleModules::route('/'),
        ];
    }
}
