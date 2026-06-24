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

namespace App\Filament\Resources\UserFollows;

use App\Filament\Resources\UserFollows\Pages\ManageUserFollows;
use App\Models\User;
use App\Models\UserFollow;
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

class UserFollowResource extends Resource
{
    protected static ?string $model = UserFollow::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Пользователи';

    protected static ?string $navigationLabel = 'Подписки';

    public static function form(Schema $schema): Schema
    {
        $users = fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all();

        return $schema->components([
            Select::make('follower_id')->label('Подписчик')->options($users)->searchable()->required(),
            Select::make('followed_id')->label('На кого подписан')->options($users)->searchable()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('follower.name')->label('Подписчик')->searchable()->sortable(),
                TextColumn::make('followed.name')->label('Читает')->searchable()->sortable(),
                TextColumn::make('created_at')->label('Создано')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUserFollows::route('/')];
    }
}
