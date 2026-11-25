<?php

namespace App\Filament\App\Resources\Branches;

use App\Filament\App\Resources\Branches\Pages\ManageBranches;
use App\Models\Branch;
use App\Traits\ScopesTenantResource;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\utils\Regex;
use Dotswan\MapPicker\Fields\Map;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;

class BranchResource extends Resource
{

    use ScopesTenantResource;

    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.business');
    }

    public static function getModelLabel(): string
    {
        return traductModel('branch');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('branch', plural: true);
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3) // Cuadrícula base de 3 columnas para alinear ambas filas
            ->components([
                // --- FILA 1: Proporción 1:2 ---
                TextInput::make('name')
                    ->required()
                    ->maxLength(150)
                    ->regex(Regex::PHRASE)
                    ->columnSpan(1) // 1 parte de 3
                    ->label(traduct("fields.branch_name")),

                TextInput::make('address')
                    ->default(null)
                    ->maxLength(255)
                    ->regex(Regex::PHRASE)
                    ->columnSpan(2) // 2 partes de 3
                    ->label(traduct("fields.address")),

                // --- FILA 2: Proporción 1:1:1 ---
                TextInput::make('longitude')
                    ->numeric()
                    ->step(0.00000001)
                    ->minValue(-180)
                    ->maxValue(180)
                    ->nullable()
                    ->readOnly()
                    ->columnSpan(1) // 1 parte de 3
                    ->label(traduct("fields.longitude")),

                TextInput::make('latitude')
                    ->numeric()
                    ->step(0.00000001)
                    ->minValue(-90)
                    ->maxValue(90)
                    ->nullable()
                    ->readOnly()
                    ->columnSpan(1) // 1 parte de 3
                    ->label(traduct("fields.latitude")),

                TextInput::make('allowed_radius')
                    ->numeric()
                    ->integer()
                    ->minValue(10)
                    ->maxValue(10000000)
                    ->default(100)
                    ->suffix('m')
                    ->columnSpan(1) // 1 parte de 3
                    ->label(traduct("fields.allowed_radius")),

                // --- FILA 3: Ancho completo y mayor altura para el mapa ---
                Map::make('location')
                    ->label('Ubicación en el mapa')
                    ->columnSpanFull() // Ocupa las 3 columnas
                    ->extraStyles([
                        'min-height: 500px', // Aumenta el alto del mapa para mayor comodidad
                        'z-index: 1',
                    ])
                    ->defaultLocation(latitude: -9.0745, longitude: -78.5936)
                    ->draggable(true)
                    ->clickable(true)
                    ->zoom(16)
                    ->showMarker(true)
                    ->showFullscreenControl(true)
                    ->showZoomControl(true)
                    ->showMyLocationButton(true)
                    ->live()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        if (is_array($state)) {
                            $set('latitude', $state['lat'] ?? null);
                            $set('longitude', $state['lng'] ?? null);
                        }
                    })
                    ->afterStateHydrated(function (mixed $state, ?Model $record, Set $set): void {
                        if ($record?->latitude && $record?->longitude) {
                            $set('location', [
                                'lat' => (float) $record->latitude,
                                'lng' => (float) $record->longitude,
                            ]);
                        }
                    })
                    ->dehydrated(false),
            ]);
    }


    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Sucursal')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('tenant.name')
                                    ->label(traduct("fields.tenant")),
                                TextEntry::make('name')
                                    ->label(traduct("fields.branch_name")),
                                TextEntry::make('address')
                                    ->placeholder('-')
                                    ->label(traduct("fields.adress")),
                            ]),

                        Tab::make('Ubicación')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->columns(3)
                            ->schema([
                                TextEntry::make('latitude')
                                    ->copyable()
                                    ->placeholder('-')
                                    ->label(traduct("fields.latitude")),
                                TextEntry::make('longitude')
                                    ->copyable()
                                    ->placeholder('-')
                                    ->label(traduct("fields.longitude")),
                                TextEntry::make('allowed_radius')
                                    ->numeric()
                                    ->placeholder('-')
                                    ->label(traduct("fields.allowed_radius")),
                            ]),

                        Tab::make('Auditoría')
                            ->icon(Heroicon::OutlinedClock)
                            ->columns(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->label(traduct("fields.created_at")),
                                TextEntry::make('updated_at')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->label(traduct("fields.updated_at")),
                                TextEntry::make('deleted_at')
                                    ->dateTime('d/m/Y H:i')
                                    ->visible(fn(Branch $record): bool => $record->trashed())
                                    ->label(traduct("fields.deleted_at")),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(traduct("fields.branch_name")),
                TextColumn::make('address')
                    ->searchable()
                    ->label(traduct("fields.address")),
                TextColumn::make('latitude')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.latitude")),
                TextColumn::make('longitude')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.longitude")),
                TextColumn::make('allowed_radius')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.allowed_radius")),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.created")),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.updated_at")),
                TextColumn::make('deleted_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.deleted_at")),
            ])
            ->stackedOnMobile()
            ->recordClasses(fn(Branch $record) => $record->trashed() ? 'is-trashed' : null)
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->label(__('actions.soft_delete'))
                    ->modalHeading(__('modals.trash.heading'))
                    ->modalDescription(__('modals.trash.description'))
                    ->icon('heroicon-m-archive-box'),

                RestoreAction::make()
                    ->label(__('actions.restore'))
                    ->modalHeading(__('modals.restore.heading'))
                    ->modalDescription(__('modals.restore.description'))
                    ->color('info'),

                ForceDeleteAction::make()
                    ->label(__('actions.delete'))
                    ->modalHeading(__('modals.force_delete.heading'))
                    ->modalDescription(__('modals.force_delete.description'))
                    ->color('danger')
                    ->before(function (ForceDeleteAction $action, Model $record) {
                        if (
                            $record->employees()->withTrashed()->exists() ||
                            $record->attendanceRecords()->exists() ||
                            $record->holidays()->exists()
                        ) {
                            Notification::make()
                                ->danger()
                                ->title(__('messages.cannot_delete_title'))
                                ->body(__('messages.restrict_delete_body', [
                                    'entity' => traductModel('plan'),
                                    'name'   => $record->name,
                                ]))
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading(__('modals.bulk.trash.heading'))
                        ->modalDescription(__('modals.bulk.trash.description')),

                    RestoreBulkAction::make()
                        ->modalHeading(__('modals.bulk.restore.heading'))
                        ->modalDescription(__('modals.bulk.restore.description')),

                    ForceDeleteBulkAction::make()
                        ->modalHeading(__('modals.bulk.force_delete.heading'))
                        ->modalDescription(__('modals.bulk.force_delete.description'))
                        ->before(function (ForceDeleteBulkAction $action, Collection $records) {
                            foreach ($records as $record) {
                                if (
                                    $record->employees()->withTrashed()->exists() ||
                                    $record->attendanceRecords()->exists() ||
                                    $record->holidays()->exists()
                                ) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.cannot_delete_title'))
                                        ->body(__('messages.restrict_delete_body', [
                                            'entity' => traductModel('branch'), // Cambiado a branch
                                            'name'   => $record->name,
                                        ]))
                                        ->persistent()
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBranches::route('/'),
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
