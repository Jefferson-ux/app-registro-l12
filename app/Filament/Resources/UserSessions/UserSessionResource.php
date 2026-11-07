<?php

namespace App\Filament\Resources\UserSessions;

use App\Filament\Resources\UserSessions\Pages\ManageUserSessions;
use App\Models\User;
use App\Models\UserSession;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserSessionResource extends Resource
{
    protected static ?string $model = UserSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.access_control');
    }

    public static function getModelLabel(): string
    {
        return traductModel('user_session');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('user_session', plural: true);
    }

    protected static ?string $recordTitleAttribute = 'name';


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('tokenable');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->defaultSort('last_used_at', 'desc')
            ->columns([
                TextColumn::make('tokenable.name')
                    ->label('Usuario')
                    ->searchable(query: fn(Builder $query, string $search) => $query->whereHasMorph(
                        'tokenable',
                        [User::class],
                        fn(Builder $q) => $q
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                    )),

                TextColumn::make('tokenable.email')
                    ->label('Correo'),

                TextColumn::make('name')
                    ->label('Sesión / dispositivo')
                    ->searchable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'En línea' => 'success',
                        'Expirada' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('duracion')
                    ->label('Tiempo de sesión')
                    ->state(fn(UserSession $record) => $record->created_at?->diffForHumans(null, true)),

                TextColumn::make('inactividad')
                    ->label('Inactiva hace')
                    ->state(fn(UserSession $record) => $record->last_used_at?->diffForHumans(null, true) ?? 'Sin uso'),

                TextColumn::make('created_at')
                    ->label('Iniciada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Último uso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Nunca'),

                TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin expiración')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('usuario')
                    ->label('Usuario')
                    ->searchable()
                    ->options(fn() => User::pluck('name', 'id'))
                    ->query(fn(Builder $query, array $data) => filled($data['value'])
                        ? $query->where('tokenable_type', User::class)
                        ->where('tokenable_id', $data['value'])
                        : $query),

                Filter::make('en_linea')
                    ->label('Solo en línea (5 min)')
                    ->query(fn(Builder $query) => $query->where('last_used_at', '>', now()->subMinutes(5))),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Cerrar sesión')
                    ->modalHeading('Cerrar esta sesión')
                    ->modalDescription('El dispositivo perderá el acceso de inmediato.')
                    ->successNotificationTitle('Sesión cerrada'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Cerrar sesiones seleccionadas'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUserSessions::route('/'),
        ];
    }
}
