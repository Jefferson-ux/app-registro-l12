<?php

namespace App\Filament\App\Resources\AttendanceSessions;

use App\Filament\App\Resources\AttendanceSessions\Pages\ManageAttendanceSessions;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Traits\ScopesTenantResource;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceSessionResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = AttendanceSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.attendance');
    }

    public static function getModelLabel(): string
    {
        return traductModel('attendance_session');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('attendance_session', plural: true);
    }
    

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee','id')
                    ->getOptionLabelFromRecordUsing(fn (Employee $record): string => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name']) // Permite buscar por cualquiera de los tres campos
                    ->preload()
                    ->required()
                    ->label(traductModel('employee')),
                DatePicker::make('attendance_date')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->label(traduct("fields.attendance_date")),
                DateTimePicker::make('check_in_at')
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')    
                    ->label(traduct("fields.check_in_at")),
                DateTimePicker::make('check_out_at')
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->label(traduct("fields.check_out_at")),
                TextInput::make('scheduled_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->maxValue(100000000)
                    ->label(traduct("fields.scheduled_minutes")),
                TextInput::make('worked_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->maxValue(100000000)
                    ->label(traduct("fields.worked_minutes")),
                TextInput::make('late_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->maxValue(100000000)
                    ->label(traduct("fields.late_minutes")),
                TextInput::make('early_leave_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->maxValue(100000000)
                    ->label(traduct("fields.early_leave_minutes")),
                TextInput::make('overtime_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->maxValue(100000000)
                    ->label(traduct("fields.overtime_minutes")),
                Select::make('status')
                    ->options([
                        'present' => traductShort("status.session.present"),
                        'late' => traductShort("status.session.late"),
                        'absent' => traductShort("status.session.absent"),
                        'incomplete' => traductShort("status.session.incomplete"),
                        'holiday' => traductShort("status.session.holiday"),
                        'leave' => traductShort("status.session.leave"),
                    ])
                    ->default('present')
                    ->required()
                    ->label(traduct('fields.status')),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant.name')
                    ->label(traduct('fields.tenant')),
                TextEntry::make('employee.id')
                    ->label(traductModel('employee')),
                TextEntry::make('attendance_date')
                    ->label(traduct('fields.attendance_date'))
                    ->date(),
                TextEntry::make('check_in_at')
                    ->label(traduct('fields.check_in_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('check_out_at')
                    ->label(traduct('fields.check_out_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('scheduled_minutes')
                    ->label(traduct('fields.scheduled_minutes'))
                    ->numeric(),
                TextEntry::make('worked_minutes')
                    ->label(traduct('fields.worked_minutes'))
                    ->numeric(),
                TextEntry::make('late_minutes')
                    ->label(traduct('fields.late_minutes'))
                    ->numeric(),
                TextEntry::make('early_leave_minutes')
                    ->label(traduct('fields.early_leave_minutes'))
                    ->numeric(),
                TextEntry::make('overtime_minutes')
                    ->label(traduct('fields.overtime_minutes'))
                    ->numeric(),
                TextEntry::make('status')
                    ->label(traduct('fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => traductShort('status.session.present'),
                        'late' => traductShort('status.session.late'),
                        'absent' => traductShort('status.session.absent'),
                        'incomplete' => traductShort('status.session.incomplete'),
                        'holiday' => traductShort('status.session.holiday'),
                        'leave' => traductShort('status.session.leave'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        'incomplete' => 'warning',
                        'holiday', 'leave' => 'gray',
                        default => 'gray',
                    }),
                TextEntry::make('created_at')
                    ->label(traduct('fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(traduct('fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('employee.id')
                    ->label(traductModel('employee'))
                    ->searchable(),
                TextColumn::make('attendance_date')
                    ->label(traduct('fields.attendance_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(traduct('fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => traductShort('status.session.present'),
                        'late' => traductShort('status.session.late'),
                        'absent' => traductShort('status.session.absent'),
                        'incomplete' => traductShort('status.session.incomplete'),
                        'holiday' => traductShort('status.session.holiday'),
                        'leave' => traductShort('status.session.leave'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        'incomplete' => 'warning',
                        'holiday', 'leave' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('check_in_at')
                    ->label(traduct('fields.check_in_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('check_out_at')
                    ->label(traduct('fields.check_out_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scheduled_minutes')
                    ->label(traduct('fields.scheduled_minutes'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('worked_minutes')
                    ->label(traduct('fields.worked_minutes'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('late_minutes')
                    ->label(traduct('fields.late_minutes'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('early_leave_minutes')
                    ->label(traduct('fields.early_leave_minutes'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('overtime_minutes')
                    ->label(traduct('fields.overtime_minutes'))
                    ->numeric()
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => ManageAttendanceSessions::route('/'),
        ];
    }
}
