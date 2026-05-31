<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'пользователь';

    protected static ?string $pluralModelLabel = 'пользователи';

    /**
     * @return array<string, string>
     */
    public static function getRoleOptions(): array
    {
        return [
            User::ROLE_USER => 'Пользователь',
            User::ROLE_ADMIN => 'Администратор',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Имя')
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->disabled(),
                Forms\Components\Select::make('role')
                    ->label('Роль')
                    ->options(static::getRoleOptions())
                    ->disabled(function (?User $record): bool {
                        $currentUser = Filament::auth()->user();

                        return $record instanceof User &&
                            $currentUser instanceof User &&
                            $record->is($currentUser);
                    })
                    ->required(),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Подтвержден')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null || $state === '') {
                            return 'Не назначена';
                        }

                        return static::getRoleOptions()[$state] ?? $state;
                    })
                    ->color(fn (?string $state): string => $state === User::ROLE_ADMIN ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Подтвержден')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options(static::getRoleOptions()),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Подтвержден')
                    ->nullable(),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
