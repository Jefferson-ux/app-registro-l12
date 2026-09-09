<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\ManageTenants;
use App\Models\Tenant;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Multitenancy';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.multitenancy');
    }

    public static function getModelLabel(): string
    {
        return traductModel('tenant');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('tenant',plural:true);
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Innecesario
                //TextInput::make('uuid')->label('UUID')->required(),
                TextInput::make('name')
                    ->label('Tenant Name')
                    ->required()
                    ->maxLength(150)
                    ->label(traduct('fields.name')),
                TextInput::make('business_name')
                    ->label(traduct('fields.business_name'))
                    ->default(null)
                    ->maxLength(200),
                TextInput::make('tax_id')
                    ->label(traduct('fields.tax_id'))
                    ->default(null)
                    ->maxLength(50),
                TextInput::make('email')
                    ->label(traduct('fields.email'))
                    ->email()
                    ->maxLength(150)
                    ->default(null),
                TextInput::make('phone')
                    ->label(traduct('fields.phone'))
                    ->tel()
                    ->maxLength(50)
                    ->default(null),
                TextInput::make('country')
                    ->label(traduct('fields.country'))
                    ->maxLength(100)
                    ->readOnly() // TODO bloqueado por el momento
                    ->default('Perú'),
                TextInput::make('timezone')
                    ->label(traduct('fields.timezone'))
                    ->maxLength(100)
                    ->default('America/Lima')
                    ->readOnly() 
                    ->required(),
                FileUpload::make('logo')
                    ->label(traduct('fields.logo'))
                    ->image()
                    ->directory('tenants/logos'),// notes: Directorio de almacenado
                Select::make('status')
                    ->label(traduct('fields.status'))
                    ->options([
                        'trial' => traduct('status.trial'),
                        'active' => traduct('status.active'),
                        'suspended' => traduct('status.suspended'),
                        'inactive' => traduct('status.inactive'),
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
                    ->label(traduct('fields.trial_ends_at'))
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
                    ->label(traduct('fields.logo'))
                    ->placeholder('Sin logo')
                    ->imageSize(400)
                    ->alignCenter()
                    ->columnSpanFull() // Ocupa toda la fila
                    ->circular()
                    ->openUrlInNewTab(), // 👁️ Permite verla en tamaño completo al hacer click,
                TextEntry::make('uuid')
                    ->label(traduct('fields.uuid'))
                    ->icon('heroicon-m-identification')
                    ->columnSpanFull() // Ocupa toda la fila
                    ->copyable(),
                    
                TextEntry::make('name')
                    ->icon('heroicon-m-building-office')
                    ->label(traduct('fields.name'))
                    ->placeholder('-'),
                TextEntry::make('business_name')
                    ->icon('heroicon-m-briefcase')
                    ->label(traduct('fields.business_name'))
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->label(traduct('fields.tax_id'))
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label(traduct('fields.email'))
                    ->icon('heroicon-m-envelope') // ✉️ Agrega el icono de correo
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->icon('heroicon-m-phone')
                    ->placeholder('-')
                    ->label(traduct('fields.phone')),
                TextEntry::make('country')
                    ->label(traduct('fields.country'))
                    ->placeholder('-'),
                TextEntry::make('timezone')
                    ->icon('heroicon-m-globe-alt')
                    ->label(traduct('fields.timezone')),
                TextEntry::make('status')
                    ->label(traduct('fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended' => 'warning',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('trial_ends_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct('fields.trial_ends_at')),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct('fields.created_at')),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct('fields.updated_at')),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Tenant $record): bool => $record->trashed())
                    ->label(traduct('fields.deleted_at')),
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
                    ->label(traduct('fields.logo'))
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->color(fn ($record) => $record->trashed() ? 'gray' : 'default')
                    ->sortable()
                    ->label(traduct('fields.name')),
                TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label(traduct('fields.business_name')),
                TextColumn::make('tax_id')
                    ->label(traduct('fields.tax_id')),
                TextColumn::make('email')
                    ->label(traduct('fields.email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(traduct('fields.phone'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->label(traduct('fields.country'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('timezone')
                    ->label(traduct('fields.timezone')),
                TextColumn::make('status')
                    ->label(traduct('fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended'=>'warning', 
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('trial_ends_at')
                    ->label(traduct('fields.trial_ends_at'))
                    ->dateTime()
                    ->date('d/m/Y')
                    ->sortable(),
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
