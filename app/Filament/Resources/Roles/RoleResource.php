<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    protected static function allowedModels(): array
    {
        return ['Role', 'Tenant', 'Subscription', 'Plan', 'User', 'AuditLog'];
    }

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.access_control');
    }

    public static function getModelLabel(): string
    {
        return traductModel('role');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('role', plural: true);
    }



    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Rol')
                    ->columns([
                        'default' => 1,
                        'sm'      => 2,
                        'lg'      => 3,
                    ])
                    ->schema([

                        TextInput::make('name')
                            ->required(),
                        TextInput::make('guard_name')
                            ->default('web')
                            ->required(),

                        Toggle::make('select_all_global')
                            ->label(__('sections.helpers.select_all_global'))
                            ->onIcon('heroicon-o-shield-check')
                            ->offIcon('heroicon-o-shield-exclamation')
                            ->inline(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                foreach (Permission::all()->groupBy(fn($p) => explode(':', $p->name)[1] ?? 'Otros') as $resourceName => $group) {
                                    $set("permissions_{$resourceName}", $state ? $group->pluck('id')->toArray() : []);
                                }
                            })
                            ->dehydrated(false),

                    ])
                    ->columnSpanFull(),


                Tabs::make('Permisos')
                    ->tabs(
                        Permission::all()
                            ->filter(fn($p) => in_array(explode(':', $p->name)[1] ?? '', static::allowedModels()))
                            ->groupBy(fn($p) => explode(':', $p->name)[1] ?? traduct('permissions.others'))
                            ->map(function ($group, $resourceName) {
                                $tabTitle = traductModel(Str::snake($resourceName));
                                return Tab::make($resourceName)
                                    ->label($tabTitle)
                                    ->schema([

                                        Toggle::make("select_all_{$resourceName}")
                                            ->label(__('sections.helpers.select_all'))
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) use ($group, $resourceName) {
                                                $set(
                                                    "permissions_{$resourceName}",
                                                    $state ? $group->pluck('id')->toArray() : []
                                                );
                                            })
                                            ->dehydrated(false),

                                        CheckboxList::make("permissions_{$resourceName}")

                                            ->label(traduct("fields.permission") . ": " . traductModel(Str::snake($resourceName), $plural = true))
                                            ->options(
                                                $group->sortBy(function ($permission) {
                                                    // 1. Extraemos la acción original (ej: "ViewAny")
                                                    $action = explode(':', $permission->name)[0];

                                                    // 2. Definimos el orden exacto que tú quieres (menor número = aparece primero)
                                                    $order = [
                                                        'ViewAny'          => 1,  // Listar / Ver Todo (Primero)
                                                        'View'             => 2,  // Ver
                                                        'Create'           => 3,  // Crear
                                                        'Update'           => 4,  // Editar
                                                        'Replicate'        => 5,  // Duplicar
                                                        'Reorder'          => 6,  // Reordenar
                                                        'Restore'          => 7,  // Restaurar
                                                        'RestoreAny'       => 8,  // Restaurar Selección
                                                        'Delete'           => 9,  // Eliminar
                                                        'DeleteAny'        => 10, // Eliminar Selección
                                                        'ForceDelete'      => 11, // Forzar Eliminación
                                                        'ForceDeleteAny'   => 12, // Forzar Eliminación Múltiple (Al final)
                                                    ];

                                                    // Si por alguna razón llega una acción que no mapeaste, va al final del todo (99)
                                                    return $order[$action] ?? 99;
                                                })
                                                    ->mapWithKeys(fn($permission) => [
                                                        // 3. Ya ordenados, creamos la estructura ID => Traducción
                                                        $permission->id => traduct("permissions." . explode(':', $permission->name)[0]),
                                                    ])
                                            )
                                            ->columns([
                                                'default' => 1, // Pantallas móviles (1 columna)
                                                'sm'      => 2, // Tabletas pequeñas (2 columnas)
                                                'lg'      => 3, // Pantallas grandes (3 columnas)
                                            ])
                                            ->gridDirection(GridDirection::Row),
                                    ]);
                            })
                            ->values()
                            ->toArray()
                    )
                    ->columnSpanFull(),


            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('guard_name'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('tenant_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record) {
                        $currentIds = $record->permissions->pluck('id')->toArray();

                        foreach (Permission::all()->groupBy(fn($p) => explode(':', $p->name)[1] ?? 'Otros') as $resourceName => $group) {
                            $data["permissions_{$resourceName}"] = $group->pluck('id')
                                ->intersect($currentIds)
                                ->toArray();
                        }

                        return $data;
                    })
                    ->after(function ($record, array $data) {
                        $allSelectedIds = collect($data)
                            ->filter(fn($value, $key) => str_starts_with($key, 'permissions_'))
                            ->flatten()
                            ->toArray();

                        $permNames = Permission::whereIn('id', $allSelectedIds)->pluck('name');
                        $record->syncPermissions($permNames);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
