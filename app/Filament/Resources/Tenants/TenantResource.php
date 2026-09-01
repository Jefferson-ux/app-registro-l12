<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\ManageTenants;
use App\Models\Tenant;
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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Innecesario
                //TextInput::make('uuid')->label('UUID')->required(),
                TextInput::make('name')
                    ->label('Tenant Name')
                    ->required()
                    ->maxLength(150),
                TextInput::make('business_name')
                    ->label('Razón Social')
                    ->default(null)
                    ->maxLength(200),
                TextInput::make('tax_id')
                    ->label('RUC / NIT')
                    ->default(null)
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(150)
                    ->default(null),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(50)
                    ->default(null),
                TextInput::make('country')
                    ->label('País')
                    ->maxLength(100)
                    ->default(null),
                TextInput::make('timezone')
                    ->label('Zona horaria')
                    ->maxLength(100)
                    ->default('America/Lima')
                    ->required(),
                FileUpload::make('logo')
                    ->label('Logo corporativo')
                    ->image()
                    ->directory('tenants/logos'),// notes: Directorio de almacenado
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Activo',
                        'suspended' => 'Suspendido',
                        'inactive' => 'Inactivo',
                    ]),
                DateTimePicker::make('trial_ends_at')
                    ->label('Fin del periodo de prueba'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('uuid')
                    ->label('UUID')
                    ->copyable(),
                TextEntry::make('name'),
                TextEntry::make('business_name')
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->icon('heroicon-m-phone')
                    ->placeholder('-'),
                TextEntry::make('country')
                    ->placeholder('-'),
                TextEntry::make('timezone'),
                ImageEntry::make('logo')
                        ->label('Logo')
                        ->placeholder('Sin logo')
                        ->circular(),
                TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'trial' => 'warning',
                                'suspended', 'inactive' => 'danger',
                                default => 'gray',
                            }),
                TextEntry::make('trial_ends_at')
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
                    ->visible(fn (Tenant $record): bool => $record->trashed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                //TextColumn::make('uuid')
                //    ->label('UUID'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('business_name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tax_id')
                    ->label('RUC/DNI'),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('timezone'),
                TextColumn::make('logo'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'warning',
                        'suspended', 'inactive' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('trial_ends_at')
                    ->dateTime()
                    ->date('d/m/Y')
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
                EditAction::make(),
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
            'index' => ManageTenants::route('/'),
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
