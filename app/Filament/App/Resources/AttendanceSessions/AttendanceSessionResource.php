<?php

namespace App\Filament\App\Resources\AttendanceSessions;

use App\Filament\App\Resources\AttendanceSessions\Pages\ManageAttendanceSessions;
use App\Models\AttendanceSession;
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
    protected static ?string $model = AttendanceSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('employee_id')
                    ->relationship('employee', 'id')
                    ->required(),
                DatePicker::make('attendance_date')
                    ->required(),
                DateTimePicker::make('check_in_at'),
                DateTimePicker::make('check_out_at'),
                TextInput::make('scheduled_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('worked_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('late_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('early_leave_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('overtime_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options([
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'incomplete' => 'Incomplete',
            'holiday' => 'Holiday',
            'leave' => 'Leave',
        ])
                    ->default('present')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant.name')
                    ->label('Tenant'),
                TextEntry::make('employee.id')
                    ->label('Employee'),
                TextEntry::make('attendance_date')
                    ->date(),
                TextEntry::make('check_in_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('check_out_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('scheduled_minutes')
                    ->numeric(),
                TextEntry::make('worked_minutes')
                    ->numeric(),
                TextEntry::make('late_minutes')
                    ->numeric(),
                TextEntry::make('early_leave_minutes')
                    ->numeric(),
                TextEntry::make('overtime_minutes')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
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
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('employee.id')
                    ->searchable(),
                TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_in_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('check_out_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scheduled_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('worked_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('late_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('early_leave_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overtime_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
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
