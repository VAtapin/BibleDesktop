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

namespace App\Filament\Resources\UserFriendships;

use App\Filament\Resources\UserFriendships\Pages\ManageUserFriendships;
use App\Models\User;
use App\Models\UserFriendship;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserFriendshipResource extends Resource
{
    protected static ?string $model = UserFriendship::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Пользователи';

    protected static ?string $navigationLabel = 'Друзья';

    public static function form(Schema $schema): Schema
    {
        $users = fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all();

        return $schema->components([
            Select::make('requester_id')->label('Инициатор')->options($users)->searchable()->required(),
            Select::make('addressee_id')->label('Другой пользователь')->options($users)->searchable()->required(),
            Select::make('status')->label('Статус')->options([
                'pending' => 'Ожидает',
                'accepted' => 'Принято',
                'rejected' => 'Отклонено',
            ])->required()->default('pending'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('requester.name')->label('Инициатор')->searchable()->sortable(),
                TextColumn::make('addressee.name')->label('Другой пользователь')->searchable()->sortable(),
                TextColumn::make('status')->label('Статус')->badge()->sortable(),
                TextColumn::make('created_at')->label('Создано')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUserFriendships::route('/')];
    }
}
