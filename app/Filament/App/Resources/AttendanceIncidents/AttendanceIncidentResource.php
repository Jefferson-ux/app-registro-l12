<?php

namespace App\Filament\App\Resources\AttendanceIncidents;

use App\Filament\App\Resources\AttendanceIncidents\Pages\ManageAttendanceIncidents;
use App\Models\AttendanceIncident;
use App\Models\AttendanceSession;
use App\Models\Employee;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceIncidentResource extends Resource
{
    protected static ?string $model = AttendanceIncident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.attendance');
    }

    public static function getModelLabel(): string
    {
        return traductModel('attendance_incident');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('attendance_incident', plural: true);
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->label(traduct("fields.tenant")),
                Select::make('employee_id')
                    ->relationship('employee','id')
                    ->getOptionLabelFromRecordUsing(fn (Employee $record): string => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name']) // Permite buscar por cualquiera de los tres campos
                    ->preload()
                    ->live() // necesario para que el segundo Select reaccione a este cambio
                    ->afterStateUpdated(fn (Set $set) => $set('attendance_session_id', null)) 
                    ->required()
                    ->label(traduct('fields.employee')),
                Select::make('attendance_session_id')
                    ->relationship(
                        'attendanceSession',
                        'id',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $query
                            ->when($get('employee_id'), fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
                    )
                    ->getOptionLabelFromRecordUsing(fn (AttendanceSession $record): string => "{$record->attendance_date}")
                    ->preload()
                    ->required()
                    ->label(traductModel("attendance_session")),
                Select::make('incident_type')
                    ->options([
                        'late' => traduct('fields.incident_late'),
                        'absence' => traduct('fields.incident_absence'),
                        'early_leave' => traduct('fields.incident_early_leave'),
                        'missing_check_in' => traduct('fields.incident_missing_check_in'),
                        'missing_check_out' => traduct('fields.incident_missing_check_out'),
                        'manual' => traduct('fields.incident_manual'),
                    ])
                    ->required()
                    ->label(traduct("fields.incident_type")),
                DatePicker::make('incident_date')
                    ->required()
                    ->label(traduct("fields.incident_date"))
                    ->displayFormat('d/m/Y')
                    ->native(false),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull()
                    ->label(traduct("fields.description")),
                Select::make('status')
                    ->options([
                            'pending' => traduct('status.pending'),
                            'justified' => traduct('status.justified'),
                            'approved' => traduct('status.approved'),
                            'rejected' => traduct('status.rejected'),
                            'cancelled' => traduct('status.cancelled'),
                        ])
                    ->default('pending')
                    ->required()
                    ->label(traduct("fields.status")),
                Select::make('resolved_by')
                    ->relationship('resolver', 'name') 
                    ->searchable()
                    ->preload()
                    ->default(null)
                    ->label(traduct("fields.resolved_by")),
                DateTimePicker::make('resolved_at')
                    ->label(traduct("fields.resolved_at"))
                    ->displayFormat('d/m/Y')
                    ->native(false),
                Textarea::make('resolution_notes')
                    ->default(null)
                    ->columnSpanFull()
                    ->label(traduct("fields.resolution_notes")),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
            TextEntry::make('tenant.name')
                ->label(traduct('fields.tenant')),
            TextEntry::make('employee.id')
                ->label(traduct('fields.employee')),
            TextEntry::make('attendanceSession.id')
                ->label(traductModel('attendance_session')),
            TextEntry::make('incident_type')
                ->label(traduct('fields.incident_type'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'late' => traduct('fields.incident_late'),
                    'absence' => traduct('fields.incident_absence'),
                    'early_leave' => traduct('fields.incident_early_leave'),
                    'missing_check_in' => traduct('fields.incident_missing_check_in'),
                    'missing_check_out' => traduct('fields.incident_missing_check_out'),
                    'manual' => traduct('fields.incident_manual'),
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'late', 'early_leave' => 'warning',
                    'absence', 'missing_check_in', 'missing_check_out' => 'danger',
                    'manual' => 'gray',
                    default => 'gray',
                }),
            TextEntry::make('incident_date')
                ->label(traduct('fields.incident_date'))
                ->date(),
            TextEntry::make('description')
                ->label(traduct('fields.description'))
                ->placeholder('-')
                ->columnSpanFull(),
            TextEntry::make('status')
                ->label(traduct('fields.status'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'pending' => traduct('status.pending'),
                    'justified' => traduct('status.justified'),
                    'approved' => traduct('status.approved'),
                    'rejected' => traduct('status.rejected'),
                    'cancelled' => traduct('status.cancelled'),
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'justified', 'approved' => 'success',
                    'rejected' => 'danger',
                    'cancelled' => 'gray',
                    default => 'gray',
                }),
            TextEntry::make('resolved_by')
                ->label(traduct('fields.resolved_by'))
                ->numeric()
                ->placeholder('-'),
            TextEntry::make('resolved_at')
                ->label(traduct('fields.resolved_at'))
                ->dateTime()
                ->placeholder('-'),
            TextEntry::make('resolution_notes')
                ->label(traduct('fields.resolution_notes'))
                ->placeholder('-')
                ->columnSpanFull(),
            TextEntry::make('created_at')
                ->label(traduct('fields.created_at'))
                ->dateTime()
                ->placeholder('-'),
            TextEntry::make('updated_at')
                ->label(traduct('fields.updated_at'))
                ->dateTime()
                ->placeholder('-'),
            TextEntry::make('deleted_at')
                ->label(traduct('fields.deleted_at'))
                ->dateTime()
                ->visible(fn (AttendanceIncident $record): bool => $record->trashed()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('employee.id')
                    ->label(traduct('fields.employee'))
                    ->searchable(),
                TextColumn::make('attendanceSession.id')
                    ->label(traduct('fields.attendance_session'))
                    ->searchable(),
                TextColumn::make('incident_type')
                    ->label(traduct('fields.incident_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'late' => traduct('fields.incident_late'),
                        'absence' => traduct('fields.incident_absence'),
                        'early_leave' => traduct('fields.incident_early_leave'),
                        'missing_check_in' => traduct('fields.incident_missing_check_in'),
                        'missing_check_out' => traduct('fields.incident_missing_check_out'),
                        'manual' => traduct('fields.incident_manual'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'late', 'early_leave' => 'warning',
                        'absence', 'missing_check_in', 'missing_check_out' => 'danger',
                        'manual' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('incident_date')
                    ->label(traduct('fields.incident_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(traduct('fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => traduct('status.pending'),
                        'justified' => traduct('status.justified'),
                        'approved' => traduct('status.approved'),
                        'rejected' => traduct('status.rejected'),
                        'cancelled' => traduct('status.cancelled'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'justified', 'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('resolved_by')
                    ->label(traduct('fields.resolved_by'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resolved_at')
                    ->label(traduct('fields.resolved_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageAttendanceIncidents::route('/'),
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
