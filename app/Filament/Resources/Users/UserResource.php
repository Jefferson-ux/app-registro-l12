<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label('Empresa / Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable() // Búsqueda AJAX para no saturar memoria
                    ->preload() // Precarga limpia de opciones
                    ->default(null)
                    ->nullable(),
                TextInput::make('name')
                    ->maxLength(150)
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(150)
                    ->unique(ignoreRecord:true)
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->revealable() // Permite mostrar/ocultar el texto con el icono de ojo
                    ->password()
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state)) // No la sobrescribe si se deja vacía al editar
                    ->required(fn (string $operation): bool => $operation === 'create'), // Solo requerida al crear
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'blocked' => 'Blocked'])
                    ->default('active')
                    ->required(),
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
                    ->label('Tenant')
                    ->placeholder('Sin Tenant'),
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'inactive' => 'warning',
                            'blocked' => 'danger',
                            default => 'gray',
                        }),
                TextEntry::make('last_login_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (User $record): bool => $record->trashed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Empresa')
                    ->placeholder('Sin asignar')    
                    ->searchable(),

                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'inactive' => 'warning',
                            'blocked' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'active' => 'Activo',
                            'inactive' => 'Inactivo',
                            'blocked' => 'Bloqueado',
                            default => $state,
                        }),



                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
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
                    ->hidden(fn ($record) => method_exists($record, 'trashed') && $record->trashed()),
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
