<?php

namespace App\Filament\App\Resources\EmployeeSchedules;

use App\Filament\App\Resources\EmployeeSchedules\Pages\ManageEmployeeSchedules;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeScheduleResource extends Resource
{
    protected static ?string $model = EmployeeSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.attendance');
    }

    public static function getModelLabel(): string
    {
        return traductModel('employee_schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('employee_schedule', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->label(traduct('fields.tenant')),
                Select::make('employee_id')
                    ->relationship('employee','id')
                    ->getOptionLabelFromRecordUsing(fn (Employee $record): string => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name']) // Permite buscar por cualquiera de los tres campos
                    ->preload()
                    ->required()
                    ->label(traductModel('fields.employee')),
                Select::make('work_schedule_id')
                    ->relationship('workSchedule', 'name')
                    ->required()
                    ->label(traduct("fields.schedule_name")),
                DatePicker::make('start_date')
                    ->required()
                    ->label(traduct("fields.resolved_at"))
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->label(traduct("fields.start_date")),
                DatePicker::make('end_date')
                    ->label(traduct("fields.resolved_at"))
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->label(traduct("fields.end_date")),
                Toggle::make('status')
                    ->required()
                    ->label(traduct("fields.status")),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant.name')
                    ->label(traduct("fields.tenant")),
                TextEntry::make('employee.id')
                    ->label(traduct("fields.employee")),
                TextEntry::make('workSchedule.name')
                    ->label(traduct("fields.schedule_name")),
                TextEntry::make('start_date')
                    ->date()
                    ->label(traduct("fields.start_date")),
                TextEntry::make('end_date')
                    ->date()
                    ->placeholder('-')
                    ->label(traduct("fields.end_date")),
                IconEntry::make('status')
                    ->boolean()
                    ->label(traduct("fields.status")),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct("fields.created_at")),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(traduct("fields.updated_at")),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('employee.id')
                    ->searchable()
                    ->label(traduct("fields.employee")),
                TextColumn::make('workSchedule.name')
                    ->searchable()
                    ->label(traduct("fields.schedule_name")),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->label(traduct("fields.start_date")),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->label(traduct("fields.end_date")),
                IconColumn::make('status')
                    ->boolean()
                    ->label(traduct("fields.status")),
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
            'index' => ManageEmployeeSchedules::route('/'),
        ];
    }
}
