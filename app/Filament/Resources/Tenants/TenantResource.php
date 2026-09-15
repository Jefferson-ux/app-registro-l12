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
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
                    ->readOnly() // TODO bloqueado por el momento
                    ->default('Perú'),
                TextInput::make('timezone')
                    ->label('Zona horaria')
                    ->maxLength(100)
                    ->default('America/Lima')
                    ->readOnly() 
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
                    ])
                    ->live() 
                    ->afterStateUpdated(function (?string $state, $set) { // 🎯 Quitamos 'Set' de los parámetros. ¡Filament lo resuelve solo por llamarse $set!
                        if ($state === 'trial') {
                            $set('trial_ends_at', now()->addDays(15)->toDateTimeString());
                        } else {
                            $set('trial_ends_at', null);
                        }
                    })
                    ->default('trial'),
                DateTimePicker::make('trial_ends_at')
                    ->default(now()->addDays(15)->toDateTimeString()) // Asociado por el "TRIAL" como default
                    ->label('Fin del periodo de prueba')
                    ->hidden(fn ($get) => $get('status') !== 'trial'),
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
                ImageEntry::make('logo')
                    ->label('Logo')
                    ->placeholder('Sin logo')
                    ->imageSize(400)
                    ->alignCenter()
                    ->columnSpanFull() // Ocupa toda la fila
                    ->circular()
                    ->openUrlInNewTab(), // 👁️ Permite verla en tamaño completo al hacer click,
                TextEntry::make('uuid')
                    ->label('UUID')
                    ->icon('heroicon-m-identification')
                    ->columnSpanFull() // Ocupa toda la fila
                    ->copyable(),
                    
                TextEntry::make('name')
                    ->icon('heroicon-m-building-office'),
                TextEntry::make('business_name')
                    ->icon('heroicon-m-briefcase')
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->label('RUC / NIT')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->icon('heroicon-m-envelope') // ✉️ Agrega el icono de correo
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->icon('heroicon-m-phone')
                    ->placeholder('-'),
                TextEntry::make('country')
                    ->placeholder('-'),
                TextEntry::make('timezone')
                    ->icon('heroicon-m-globe-alt'),
                TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'trial' => 'info',
                                'suspended'=>'warning', 
                                'inactive' => 'danger',
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
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->color(fn ($record) => $record->trashed() ? 'gray' : 'default')
                    ->sortable(),
                TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tax_id')
                    ->label('RUC/DNI'),
                TextColumn::make('email')
                    ->label('Email address')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('timezone'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended'=>'warning', 
                        'inactive' => 'danger',
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
                // TODO -> Filtro opcional
                /*
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Activo',
                        'suspended' => 'Suspendido',
                        'inactive' => 'Inactivo',
                ]),
                */
            ])


            ->recordClasses(fn (Model $record): ?string => 
                        method_exists($record, 'trashed') && $record->trashed() 
                            ? 'opacity-60 line-through bg-red-50 dark:bg-red-950/30' 
                            : null
                    )

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
