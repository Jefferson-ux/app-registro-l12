<?php

namespace App\Filament\App\Resources\Employees;

use App\Actions\Employee\ProcessFaceCaptureAction;
use App\Filament\App\Resources\Employees\Pages\ManageEmployees;
use App\Models\Employee;
use App\Models\Position;
use App\Traits\ScopesTenantResource;
use App\utils\Regex;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Storage;

class EmployeeResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static ?int $navigationSort = 3;



    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.business');
    }

    public static function getModelLabel(): string
    {
        return traductModel('employee');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('employee', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Formulario de Empleado')
                    ->columnSpanFull()
                    ->tabs([
                        // PESTAÑA 1: Datos Personales y de Contacto
                        Tabs\Tab::make('Datos Personales')
                            ->schema([
                                Select::make('document_type')
                                    ->label(__('fields.document_type'))
                                    ->options([
                                        'dni'      => 'DNI',
                                        'ruc'      => 'RUC',
                                        'ce'       => 'Carné de extranjería',
                                        'passport' => 'Pasaporte',
                                        'other'    => 'Otro',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('document_number', null)),
                                TextInput::make('document_number')
                                    ->label(__('fields.document_number'))
                                    ->regex(fn(Get $get): string => match ($get('document_type')) {
                                        'dni'      => Regex::DOC_DNI,
                                        'ruc'      => Regex::TAX_ID_PE,
                                        'ce'       => Regex::DOC_CE,
                                        'passport' => Regex::DOC_PASSPORT,
                                        default    => Regex::DOC_OTHER,
                                    })
                                    ->maxLength(fn(Get $get): int => match ($get('document_type')) {
                                        'dni'   => 8,
                                        'ruc'   => 11,
                                        default => 50,
                                    })
                                    ->default(null)
                                    ->dehydrateStateUsing(fn(?string $state) => $state ? strtoupper($state) : null)
                                    ->validationMessages(['regex' => 'El formato no corresponde al tipo de documento seleccionado.']),
                                TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(100)
                                    ->regex(Regex::PERSON_NAME)
                                    ->label(traduct("fields.first_name")),
                                TextInput::make('last_name')
                                    ->required()
                                    ->regex(Regex::PERSON_NAME)
                                    ->maxLength(100)
                                    ->label(traduct("fields.last_name")),
                                TextInput::make('personal_email')
                                    ->email()
                                    ->default(null)
                                    ->maxLength(150)
                                    ->label(traduct("fields.personal_email")),
                                TextInput::make('work_email')
                                    ->email()
                                    ->default(null)
                                    ->maxLength(150)
                                    ->label(traduct("fields.work_email")),
                                TextInput::make('phone')
                                    ->tel()
                                    ->regex(Regex::PHONE)
                                    ->default(null)
                                    ->maxLength(50)
                                    ->label(traduct("fields.phone")),
                            ]),

                        // PESTAÑA 2: Organización
                        Tabs\Tab::make('Organización')
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->default(null)
                                    ->label(traduct("fields.user")),
                                Select::make('branch_id')
                                    ->relationship('branch', 'name')
                                    ->default(null)
                                    ->label(traduct("fields.branch")),
                                Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->default(null)
                                    ->label(traduct("fields.department")),
                                Select::make('position_id')
                                    ->relationship('position', 'name')
                                    ->reactive()
                                    ->default(null)
                                    ->label(traduct("fields.position"))
                                    ->afterStateUpdated(function (mixed $state, Set $set, ?Employee $record) {
                                        if (!$state) {
                                            $set('employee_code', null);
                                            return;
                                        }

                                        $position = Position::find($state);

                                        if (!$position || empty($position->prefix)) {
                                            $set('employee_code', null);
                                            return;
                                        }

                                        $prefix = strtoupper(trim($position->prefix));
                                        $tenantId = filament()->getTenant()?->id;

                                        $query = Employee::where('employee_code', 'like', "{$prefix}-%")
                                            ->withTrashed();

                                        if ($tenantId) {
                                            $query->where('tenant_id', $tenantId);
                                        }

                                        if ($record && $record->exists) {
                                            $query->where('id', '!=', $record->id);
                                        }

                                        $existingCodes = $query->pluck('employee_code');

                                        $maxNumber = 0;
                                        foreach ($existingCodes as $code) {
                                            $parts = explode('-', $code);
                                            $number = intval(end($parts));
                                            if ($number > $maxNumber) {
                                                $maxNumber = $number;
                                            }
                                        }

                                        $sequence = $maxNumber + 1;
                                        $set('employee_code', sprintf('%s-%03d', $prefix, $sequence));
                                    }),
                                Select::make('supervisor_id')
                                    ->relationship(
                                        'supervisor',
                                        'id',
                                        modifyQueryUsing: fn(Builder $query, ?Employee $record) => $record
                                            ? $query->where('id', '!=', $record->id)
                                            : $query
                                    )
                                    ->default(null)
                                    ->getOptionLabelFromRecordUsing(fn(Employee $record): string => "{$record->first_name} {$record->last_name}")
                                    ->searchable(['first_name', 'last_name'])
                                    ->preload()
                                    ->label(traduct("fields.supervisor")),
                                TextInput::make('employee_code')
                                    ->required()
                                    ->maxLength(50)
                                    ->regex(Regex::CODE)
                                    ->unique(
                                        table: 'employees',
                                        column: 'employee_code',
                                        ignoreRecord: true,
                                        modifyRuleUsing: function ($rule, callable $get) {
                                            return $rule->where('tenant_id', $get('tenant_id') ?? auth()->user()->tenant_id);
                                        }
                                    )
                                    ->dehydrated()
                                    ->helperText('Se genera automáticamente según el prefijo del cargo seleccionado.')
                                    ->label(traduct("fields.employee_code")),
                                DatePicker::make('hire_date')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->label(traduct("fields.hire_date")),
                                DatePicker::make('termination_date')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->label(traduct("fields.termination_date")),
                                Select::make('employment_status')
                                    ->options([
                                        'active'     => traduct('status.active'),
                                        'inactive'   => traduct('status.inactive'),
                                        'suspended'  => traduct('status.suspended'),
                                        'terminated' => traduct('status.terminated'),
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->label(traduct("fields.employment_status")),
                            ]),

                        // PESTAÑA 3: Adicional
                        Tabs\Tab::make('Adicional')
                            ->schema([
                                ViewField::make('face_payload')
                                    ->view('filament.forms.face-capture')
                                    ->dehydrated()
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Empleado')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(traduct("sections.personal_data"))
                            ->icon(Heroicon::OutlinedUser)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('first_name')
                                    ->label(traduct("fields.first_name")),
                                TextEntry::make('last_name')
                                    ->label(traduct("fields.last_name")),
                                TextEntry::make('document_type')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.document_type")),
                                TextEntry::make('document_number')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.document_number")),
                                TextEntry::make('personal_email')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.personal_email")),
                                TextEntry::make('phone')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.phone")),
                            ]),

                        Tab::make(traduct("sections.company_data"))
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->columns(3)
                            ->schema([
                                TextEntry::make('employee_code')
                                    ->label(traduct("fields.employee_code")),
                                TextEntry::make('user.name')
                                    ->placeholder(__('empty.user'))
                                    ->label(traduct("fields.user")),
                                TextEntry::make('branch.name')
                                    ->placeholder(__('empty.branch'))
                                    ->label(traduct("fields.branch")),
                                TextEntry::make('department.name')
                                    ->placeholder(__('empty.department'))
                                    ->label(traduct("fields.department")),
                                TextEntry::make('position.name')
                                    ->placeholder(__('empty.position'))
                                    ->label(traduct("fields.position")),
                                TextEntry::make('supervisor.id')
                                    ->placeholder(__('empty.supervisor'))
                                    ->label(traduct("fields.supervisor")),
                                TextEntry::make('work_email')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.work_email")),
                                TextEntry::make('hire_date')
                                    ->date()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.hire_date")),
                                TextEntry::make('termination_date')
                                    ->date()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.termination_date")),
                                TextEntry::make('employment_status')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => traduct('status.' . $state))
                                    ->color(fn(string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'suspended' => 'warning',
                                        'terminated' => 'danger',
                                        default => 'gray',
                                    })
                                    ->label(traduct("fields.employment_status")),
                            ]),

                        Tab::make(traduct("sections.qr_audit"))
                            ->icon(Heroicon::OutlinedQrCode)
                            ->columns(3)
                            ->schema([
                                ImageEntry::make('qr_image_path')
                                    ->label(traduct("fields.qr_code"))
                                    ->state(function (Employee $record): ?string {
                                        if (! $record->qr_image_path || ! Storage::disk('local')->exists($record->qr_image_path)) {
                                            return null;
                                        }

                                        return 'data:image/png;base64,' . base64_encode(
                                            Storage::disk('local')->get($record->qr_image_path)
                                        );
                                    })
                                    ->imageWidth(200)
                                    ->imageHeight(200)
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.created_at")),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct("fields.updated_at")),
                                TextEntry::make('deleted_at')
                                    ->dateTime()
                                    ->visible(fn(Employee $record): bool => $record->trashed())
                                    ->label(traduct("fields.deleted_at")),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.user")),
                TextColumn::make('branch.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.branch")),
                TextColumn::make('department.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.department")),
                TextColumn::make('employee_code')
                    ->searchable()
                    ->sortable()
                    ->label(traduct("fields.employee_code")),
                TextColumn::make('document_type')
                    ->searchable()
                    ->label(traduct("fields.document_type")),
                TextColumn::make('document_number')
                    ->searchable()
                    ->label(traduct("fields.document_number")),
                TextColumn::make('first_name')
                    ->searchable()
                    ->label(traduct("fields.first_name")),
                TextColumn::make('last_name')
                    ->searchable()
                    ->label(traduct("fields.last_name")),
                TextColumn::make('position.name')
                    ->searchable()
                    ->placeholder(traduct('messages.empty_value'))
                    ->label(traduct("fields.position")),
                TextColumn::make('supervisor.id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.supervisor")),
                TextColumn::make('personal_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.personal_email")),
                TextColumn::make('work_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.work_email")),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.phone")),
                TextColumn::make('hire_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.hire_date")),
                TextColumn::make('termination_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.termination_date")),
                TextColumn::make('employment_status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => traduct('status.' . $state))
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'suspended' => 'warning',
                        'terminated' => 'danger',
                        default => 'gray',
                    })
                    ->label(traduct("fields.employment_status")),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.created_at")),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.updated_at")),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.deleted_at")),
            ])
            ->stackedOnMobile()
            ->recordClasses(fn(Employee $record) => $record->trashed() ? 'is-trashed' : null)
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->using(fn(array $data, Model $record): Model => app(ProcessFaceCaptureAction::class)->execute($data, $record)),
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
                            $record->schedules()->exists() ||
                            $record->attendanceRecords()->exists() ||
                            $record->attendanceSessions()->exists() ||
                            $record->attendanceIncidents()->withTrashed()->exists()
                        ) {
                            Notification::make()
                                ->danger()
                                ->title(__('messages.cannot_delete_title'))
                                ->body(__('messages.restrict_delete_body', [
                                    'entity' => traductModel('employee'),
                                    'name' => $record->employee_code,
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
                                    $record->schedules()->exists() ||
                                    $record->attendanceRecords()->exists() ||
                                    $record->attendanceSessions()->exists() ||
                                    $record->attendanceIncidents()->withTrashed()->exists()
                                ) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.cannot_delete_title'))
                                        ->body(__('messages.restrict_delete_body', [
                                            'entity' => traductModel('employee'),
                                            'name' => $record->employee_code,
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
            'index' => ManageEmployees::route('/'),
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
