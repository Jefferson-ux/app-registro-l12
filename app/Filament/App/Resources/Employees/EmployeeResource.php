<?php

namespace App\Filament\App\Resources\Employees;

use App\Filament\App\Resources\Employees\Pages\ManageEmployees;
use App\Models\Employee;
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
use Filament\Forms\Components\DatePicker;
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
                    ->default(null)
                    ->label(traduct("fields.position")),
                Select::make('supervisor_id')
                        ->relationship(
                            'supervisor',
                            'id',
                            modifyQueryUsing: fn (Builder $query, ?Employee $record) => $record
                                ? $query->where('id', '!=', $record->id)
                                : $query
                        )
                    ->default(null)
                    ->getOptionLabelFromRecordUsing(fn (Employee $record): string => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name']) // Permite buscar por cualquiera de los tres campos
                    ->preload()
                    ->label(traduct("fields.supervisor")),
                TextInput::make('employee_code')
                    ->required()
                    ->maxLength(50)
                    ->label(traduct("fields.employee_code")),
                TextInput::make('document_type')
                    ->default(null)
                    ->maxLength(30)
                    ->label(traduct("fields.document_type")),
                TextInput::make('document_number')
                    ->default(null)
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->label(traduct("fields.document_number")),
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(100)
                    ->label(traduct("fields.first_name")),
                TextInput::make('last_name')
                    ->required()
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
                    ->default(null)
                    ->maxLength(50)
                    ->label(traduct("fields.phone")),
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
                        'active' => traduct('status.active'),
                        'inactive' => traduct('status.inactive'),
                        'suspended' => traduct('status.suspended'),
                        'terminated' => traduct('status.terminated'),
                    ])
                    ->default('active')
                    ->required()
                    ->label(traduct("fields.employment_status")),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant.name')
                    ->label('Tenant')
                    ->label(traduct("fields.tenant")),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->label(traduct("fields.user")),
                TextEntry::make('branch.name')
                    ->label('Branch')
                    ->placeholder('-')
                    ->label(traduct("fields.branch")),
                TextEntry::make('department.name')
                    ->label('Department')
                    ->placeholder('-')
                    ->label(traduct("fields.department")),
                TextEntry::make('position.name')
                    ->label('Position')
                    ->placeholder('-')
                    ->label(traduct("fields.position")),
                TextEntry::make('supervisor.id')
                    ->label('Supervisor')
                    ->placeholder('-')
                    ->label(traduct("fields.supervisor")),
                TextEntry::make('employee_code')
                    ->label(traduct("fields.employee_code")),
                TextEntry::make('document_type')
                    ->placeholder('-')
                    ->label(traduct("fields.document_type")),
                TextEntry::make('document_number')
                    ->placeholder('-')
                    ->label(traduct("fields.document_number")),
                TextEntry::make('first_name')
                    ->label(traduct("fields.first_name")),
                TextEntry::make('last_name')
                    ->label(traduct("fields.last_name")),
                TextEntry::make('personal_email')
                    ->placeholder('-')
                    ->label(traduct("fields.personal_email")),
                TextEntry::make('work_email')
                    ->placeholder('-')
                    ->label(traduct("fields.work_email")),
                TextEntry::make('phone')
                    ->placeholder('-')
                    ->label(traduct("fields.phone")),
                TextEntry::make('hire_date')
                    ->date()
                    ->placeholder('-')
                    ->label(traduct("fields.hire_date")),
                TextEntry::make('termination_date')
                    ->date()
                    ->placeholder('-')
                    ->label(traduct("fields.termination_date")),
                TextEntry::make('employment_status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'suspended' => 'warning',
                        'terminated' => 'danger',
                        default => 'gray',
                    })
                    ->label(traduct("fields.employment_status")),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct("fields.created_at")),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct("fields.updated_at")),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Employee $record): bool => $record->trashed())
                    ->label(traduct("fields.deleted_at")),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([

                TextColumn::make('user.name')
                    ->searchable()
                    ->label(traduct("fields.user")),
                TextColumn::make('branch.name')
                    ->searchable()
                    ->label(traduct("fields.branch")),
                TextColumn::make('department.name')
                    ->searchable()
                    ->label(traduct("fields.department")),
                TextColumn::make('position.name')
                    ->searchable()
                    ->label(traduct("fields.position")),
                TextColumn::make('supervisor.id')
                    ->searchable()
                    ->label(traduct("fields.supervisor")),
                TextColumn::make('employee_code')
                    ->searchable()
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
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
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
