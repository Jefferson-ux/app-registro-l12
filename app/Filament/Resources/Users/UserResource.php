<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\PermissionRegistrar;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'access_control';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.access_control');
    }

    public static function getModelLabel(): string
    {
        return traductModel('user');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('user', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label(traduct('fields.tenant'))
                    ->relationship('tenant', 'name')
                    ->searchable() // Búsqueda AJAX para no saturar memoria
                    ->preload() // Precarga limpia de opciones
                    ->default(null)
                    ->nullable(),
                TextInput::make('name')
                    ->label(traduct('fields.name'))
                    ->maxLength(150)
                    ->required(),
                TextInput::make('email')
                    ->label(traduct('fields.email'))
                    ->email()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true)
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label(traduct('fields.email_verified_at'))
                    ->placeholder('-')
                    ->nullable(),
                TextInput::make('password')
                    ->revealable() // Permite mostrar/ocultar el texto con el icono de ojo
                    ->password()
                    ->maxLength(255)
                    ->label(traduct('fields.password'))
                    ->dehydrated(fn(?string $state): bool => filled($state)) // No la sobrescribe si se deja vacía al editar
                    ->required(fn(string $operation): bool => $operation === 'create'), // Solo requerida al crear
                Select::make('status')
                    ->options([
                        'active' => traduct('status.active'),
                        'inactive' => traduct('status.inactive'),
                        'blocked' => traduct('status.blocked')
                    ])
                    ->default('active')
                    ->required(),
                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->options(function ($record) {
                        $tenantId = $record?->tenant_id ?? 0;
                        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
                        return Role::pluck('name', 'id');
                    })
                    ->preload()
                    ->searchable()
                    ->saveRelationshipsUsing(null)
                    ->dehydrated(true)
                    ->required()
                    ->label(traduct('fields.status')),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1, // Celular
                'sm' => 2,      // Tablets (pantallas pequeñas)
                'lg' => 3,      // Computadoras (pantallas grandes)
            ])
            ->components([
                TextEntry::make('tenant.name')
                    ->label(traduct('fields.tenant'))
                    ->placeholder(__('sections.placeholder.not_assigned')),
                TextEntry::make('name')
                    ->label(traduct('fields.name')),
                TextEntry::make('email')
                    ->label(traduct('fields.email')),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct('fields.email_verified_at')),
                TextEntry::make('status')
                    ->badge()
                    ->label(traduct('fields.status'))
                    ->formatStateUsing(fn(string $state): string => traduct('status.' . $state))
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('last_login_at')
                    ->dateTime()
                    ->label(traduct('fields.last_login_at'))
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->label(traduct('fields.created_at'))
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->label(traduct('fields.updated_at'))
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->label(traduct('fields.deleted_at'))
                    ->visible(fn(User $record): bool => $record->trashed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('tenant.name')
                    ->label(traduct('fields.tenant'))
                    ->placeholder(__('sections.placeholder.not_assigned'))
                    ->searchable(),

                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label(traduct('fields.email'))
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                TextColumn::make('email_verified_at')
                    ->label(traduct('fields.email_verified_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(traduct('fields.status'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => traduct('status.' . $state))
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('last_login_at')
                    ->label(traduct('fields.last_login_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(traduct('fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(traduct('fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(traduct('fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->hidden(fn($record) => method_exists($record, 'trashed') && $record->trashed())


                    ->mutateRecordDataUsing(function (array $data, $record) {
                        $tenantId = $record->tenant_id ?? 0;
                        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

                        $data['roles'] = $record->roles->pluck('id')->toArray();
                        return $data;
                    })
                    ->after(function ($record, array $data) {
                        $tenantId = $record->tenant_id ?? 0;
                        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

                        $roleNames = Role::whereIn('id', $data['roles'] ?? [])->pluck('name');
                        $record->syncRoles($roleNames);
                    }),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
