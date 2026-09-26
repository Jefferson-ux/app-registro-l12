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

class RoleResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

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
                            ->required()
                            ->label(traduct('fields.role_name'))
                            ->disabled(fn($record) => $record?->is_protected ?? false),

                        TextInput::make('label')
                            ->label(traduct('fields.label'))
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
                                foreach (Permission::all()->groupBy(fn($p) => explode(':', $p->name)[1] ?? 'Otros') as $resourceName => $group) {
                                    $set("permissions_{$resourceName}", $state ? $group->pluck('id')->toArray() : []);
                                }
                            })
                            ->dehydrated(false),

                        TextInput::make('description')
                            ->label(traduct('fields.description'))
                            ->columnSpanFull(),

                    ])
                    ->columnSpanFull(),


                Tabs::make('Permisos')
                    ->hidden(fn($record) => $record?->is_protected ?? false)
                    ->tabs(
                        Permission::all()
                            ->filter(fn($p) => in_array(explode(':', $p->name)[1] ?? '', static::allowedModels()))
                            ->groupBy(fn($p) => explode(':', $p->name)[1] ?? traduct('permissions.others'))
                            ->sortBy(function ($group, $resourceName) {
                                $index = array_search($resourceName, static::allowedModels());
                                return $index !== false ? $index : 999;
                            })
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
                TextEntry::make('name'),
                TextEntry::make('label')->placeholder('----'),
                TextEntry::make('color')->placeholder('----'),
                TextEntry::make('guard_name'),
                TextEntry::make('description')->columnSpanFull(),
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
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->visible(fn($record) => !$record->is_protected),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
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
