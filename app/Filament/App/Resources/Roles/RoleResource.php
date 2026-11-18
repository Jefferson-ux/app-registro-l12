<?php

namespace App\Filament\App\Resources\Roles;

use App\Enums\RoleColor;
use App\Filament\App\Resources\Roles\Pages\ManageRoles;
use App\Models\Role;
use App\Traits\ScopesTenantResource;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use App\utils\Regex;

class RoleResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';
    public static bool $shouldRegisterGlobalSearch = false;

    protected static ?int $navigationSort = 2;

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

    protected static function colorOptions(): array
    {
        return collect(RoleColor::cases())
            ->mapWithKeys(fn(RoleColor $color): array => [
                $color->value => $color->label(),
            ])
            ->all();
    }

    protected static function filamentColor(?RoleColor $color): string
    {
        return match ($color) {
            RoleColor::Red => 'danger',
            RoleColor::Blue => 'info',
            RoleColor::Green => 'success',
            RoleColor::Amber => 'warning',
            RoleColor::Indigo => 'primary',
            RoleColor::Gray, null => 'gray',
        };
    }

    protected static function allowedModels(): array
    {
        return [
            'Role',
            'User',
            'Branch',
            'Department',
            'Employee',
            'Position',
            'WorkSchedule',
            'WorkScheduleDay',
            'EmployeeSchedule',
            'AttendanceRecord',
            'AttendanceSession',
            'AttendanceIncident',
            'Holiday',
        ];
    }

    public static function permissionGroups(): \Illuminate\Support\Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->get()
            ->filter(fn(Permission $permission) => in_array(
                explode(':', $permission->name)[1] ?? '',
                static::allowedModels(),
                true
            ))
            ->groupBy(fn(Permission $permission) => explode(':', $permission->name)[1] ?? traduct('permissions.others'))
            ->sortBy(function ($group, $resourceName) {
                $index = array_search($resourceName, static::allowedModels(), true);

                return $index !== false ? $index : 999;
            });
    }

    public static function permissionFormState(Role $role): array
    {
        $selectedIds = $role->permissions->modelKeys();
        $groups = static::permissionGroups();
        $state = [];

        foreach ($groups as $resourceName => $permissions) {
            $permissionIds = $permissions->modelKeys();
            $selectedForResource = array_values(array_intersect($permissionIds, $selectedIds));

            $state["permissions_{$resourceName}"] = $selectedForResource;
            $state["select_all_{$resourceName}"] = count($permissionIds) > 0
                && count($selectedForResource) === count($permissionIds);
        }

        $allPermissionIds = $groups->flatten(1)->pluck('id')->all();
        $selectedAllowedIds = array_values(array_intersect($allPermissionIds, $selectedIds));
        $state['select_all_global'] = count($allPermissionIds) > 0
            && count($selectedAllowedIds) === count($allPermissionIds);

        return $state;
    }

    public static function syncPermissionsFromForm(Role $role, array $data): void
    {
        if ($role->is_protected) {
            return;
        }

        $selectedIds = collect($data)
            ->filter(fn($value, $key) => str_starts_with($key, 'permissions_'))
            ->flatten()
            ->filter(fn($id) => is_numeric($id))
            ->unique()
            ->values();

        $allowedIds = static::permissionGroups()
            ->flatten(1)
            ->pluck('id')
            ->all();

        $permissions = Permission::query()
            ->where('guard_name', $role->guard_name)
            ->whereIn('id', $selectedIds->intersect($allowedIds))
            ->get();

        $role->syncPermissions($permissions);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sections.role_form.title'))
                    ->columns([
                        'default' => 1,
                        'sm'      => 2,
                        'lg'      => 3,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label(traduct('fields.role_name'))
                            ->disabled(fn($record) => $record?->is_protected ?? false),

                        TextInput::make('label')
                            ->label(traduct('fields.label'))
                            ->regex(Regex::PHRASE)
                            ->maxLength(255),

                        Select::make('color')
                            ->label(traduct('fields.color'))
                            ->options(static::colorOptions())
                            ->default(RoleColor::Gray->value)
                            ->native(false),

                        TextInput::make('guard_name')
                            ->readOnly()
                            ->hidden(fn($record) => $record?->is_protected ?? false)
                            ->label(traduct('fields.guard_name'))
                            ->default('web')
                            ->required(),

                        Toggle::make('select_all_global')
                            ->hidden(fn($record) => $record?->is_protected ?? false)
                            ->label(__('sections.helpers.select_all_global'))
                            ->onIcon('heroicon-o-shield-check')
                            ->offIcon('heroicon-o-shield-exclamation')
                            ->inline(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                foreach (static::permissionGroups() as $resourceName => $group) {
                                    $set("select_all_{$resourceName}", $state);
                                    $set("permissions_{$resourceName}", $state ? $group->pluck('id')->toArray() : []);
                                }
                            })
                            ->dehydrated(false),

                        Textarea::make('description')
                            ->label(traduct('fields.description'))
                            ->columnSpanFull(),

                    ])
                    ->columnSpanFull(),


                Tabs::make(traduct('fields.permissions'))
                    ->hidden(fn($record) => $record?->is_protected ?? false)
                    ->tabs(
                        static::permissionGroups()
                            ->map(function ($group, $resourceName) {
                                $tabTitle = traductModel(Str::snake($resourceName));
                                return Tab::make($resourceName)
                                    ->label($tabTitle)
                                    ->schema([

                                        Toggle::make("select_all_{$resourceName}")
                                            ->label(__('sections.helpers.select_all'))
                                            ->columnSpanFull()
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
                                            ->columnSpanFull()
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
                Tabs::make('Rol')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(traduct('sections.general'))
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                TextEntry::make('name')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.name')),
                                TextEntry::make('label')
                                    ->placeholder(__('empty.label'))
                                    ->label(traduct('fields.label')),
                                TextEntry::make('guard_name')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.guard_name')),
                                TextEntry::make('description')
                                    ->placeholder(__('empty.description'))
                                    ->label(traduct('fields.description'))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(traduct('sections.audit'))
                            ->icon(Heroicon::OutlinedClock)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.created_at')),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.updated_at')),
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
                    ->label(traduct('fields.role_name'))
                    ->badge()
                    ->color(
                        fn(Role $record): string => static::filamentColor($record->color)
                    )
                    ->size('4xl')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('label')
                    ->placeholder('sin etiqueta')
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
            ->stackedOnMobile()
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Role $record): array {
                        return array_merge($data, static::permissionFormState($record));
                    })
                    ->after(function (Role $record, array $data): void {
                        static::syncPermissionsFromForm($record, $data);
                    }),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->modalHeading(__('modals.force_delete.heading'))
                    ->modalDescription(__('modals.force_delete.description'))
                    ->color('danger')
                    ->visible(fn($record) => !$record->is_protected),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label(__('actions.delete'))
                    ->modalHeading(__('modals.bulk.force_delete.heading'))
                    ->modalDescription(__('modals.bulk.force_delete.description'))
                    ->color('danger')
                    ->action(function ($records) {
                        $records->filter(fn($r) => !$r->is_protected)
                            ->each(fn($r) => $r->delete());
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
