<?php

namespace App\Filament\App\Resources\WorkScheduleDays;

use App\Filament\App\Resources\WorkScheduleDays\Pages\ManageWorkScheduleDays;
use App\Models\WorkScheduleDay;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkScheduleDayResource extends Resource
{
    protected static ?string $model = WorkScheduleDay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.attendance');
    }

    public static function getModelLabel(): string
    {
        return traductModel('work_schedule_day');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('work_schedule_day', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->label(traduct('fields.tenant')),
                Select::make('work_schedule_id')
                    ->relationship('workSchedule', 'name')
                    ->required()
                    ->label(traduct('fields.schedule_name')),
                Select::make('day_of_week')
                    ->options([
                        1 => traductShort('status.day_of_week.monday'),
                        2 => traductShort('status.day_of_week.tuesday'),
                        3 => traductShort('status.day_of_week.wednesday'),
                        4 => traductShort('status.day_of_week.thursday'),
                        5 => traductShort('status.day_of_week.friday'),
                        6 => traductShort('status.day_of_week.saturday'),
                        7 => traductShort('status.day_of_week.sunday'),
                    ])
                    ->required()
                    ->native(false)
                    ->label(traduct('fields.day_of_week')),
                Toggle::make('is_working_day')
                    ->required()
                    ->label(traduct('fields.is_working_day')),
                TimePicker::make('check_in_time')
                    ->label(traduct('fields.check_in_time')),
                TimePicker::make('check_out_time')
                    ->label(traduct('fields.check_out_time')),
                TimePicker::make('break_start_time')
                    ->label(traduct('fields.break_start_time')),
                TimePicker::make('break_end_time')
                    ->label(traduct('fields.break_end_time')),
                TextInput::make('check_in_tolerance_minutes')
                    ->required()
                    ->numeric()
                    ->maxValue(10000)
                    ->default(0)
                    ->label(traduct('fields.check_in_tolerance')),
                TextInput::make('check_out_tolerance_minutes')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->maxValue(10000)
                    ->label(traduct('fields.check_out_tolerance')),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
        ->components([
            TextEntry::make('tenant.name')
                ->label(traduct('fields.tenant')),
            TextEntry::make('workSchedule.name')
                ->label(traduct('fields.schedule_name')),
            TextEntry::make('day_of_week')
                ->label(traduct('fields.day_of_week'))
                ->numeric(),
            IconEntry::make('is_working_day')
                ->label(traduct('fields.is_working_day'))
                ->boolean(),
            TextEntry::make('check_in_time')
                ->label(traduct('fields.check_in_time'))
                ->time()
                ->placeholder('-'),
            TextEntry::make('check_out_time')
                ->label(traduct('fields.check_out_time'))
                ->time()
                ->placeholder('-'),
            TextEntry::make('break_start_time')
                ->label(traduct('fields.break_start_time'))
                ->time()
                ->placeholder('-'),
            TextEntry::make('break_end_time')
                ->label(traduct('fields.break_end_time'))
                ->time()
                ->placeholder('-'),
            TextEntry::make('check_in_tolerance_minutes')
                ->label(traduct('fields.check_in_tolerance'))
                ->numeric(),
            TextEntry::make('check_out_tolerance_minutes')
                ->label(traduct('fields.check_out_tolerance'))
                ->numeric(),
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
            TextColumn::make('workSchedule.name')
                ->label(traduct('fields.schedule_name'))
                ->searchable(),
            TextColumn::make('day_of_week')
                ->label(traduct('fields.day_of_week'))
                ->numeric()
                ->sortable(),
            IconColumn::make('is_working_day')
                ->label(traduct('fields.is_working_day'))
                ->boolean(),
            TextColumn::make('check_in_time')
                ->label(traduct('fields.check_in_time'))
                ->time('g:i A')
                ->sortable(),
            TextColumn::make('check_out_time')
                ->label(traduct('fields.check_out_time'))
                ->time('g:i A')
                ->sortable(),
            TextColumn::make('break_start_time')
                ->label(traduct('fields.break_start_time'))
                ->time()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
            TextColumn::make('break_end_time')
                ->label(traduct('fields.break_end_time'))
                ->time()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
            TextColumn::make('check_in_tolerance_minutes')
                ->label(traduct('fields.check_in_tolerance'))
                ->numeric()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
            TextColumn::make('check_out_tolerance_minutes')
                ->label(traduct('fields.check_out_tolerance'))
                ->numeric()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
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
            'index' => ManageWorkScheduleDays::route('/'),
        ];
    }
}
