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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('work_schedule_id')
                    ->relationship('workSchedule', 'name')
                    ->required(),
                TextInput::make('day_of_week')
                    ->required()
                    ->numeric(),
                Toggle::make('is_working_day')
                    ->required(),
                TimePicker::make('check_in_time'),
                TimePicker::make('check_out_time'),
                TimePicker::make('break_start_time'),
                TimePicker::make('break_end_time'),
                TextInput::make('check_in_tolerance_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('check_out_tolerance_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant.name')
                    ->label('Tenant'),
                TextEntry::make('workSchedule.name')
                    ->label('Work schedule'),
                TextEntry::make('day_of_week')
                    ->numeric(),
                IconEntry::make('is_working_day')
                    ->boolean(),
                TextEntry::make('check_in_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('check_out_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('break_start_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('break_end_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('check_in_tolerance_minutes')
                    ->numeric(),
                TextEntry::make('check_out_tolerance_minutes')
                    ->numeric(),
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
                TextColumn::make('workSchedule.name')
                    ->searchable(),
                TextColumn::make('day_of_week')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_working_day')
                    ->boolean(),
                TextColumn::make('check_in_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('check_out_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('break_start_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('break_end_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('check_in_tolerance_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('check_out_tolerance_minutes')
                    ->numeric()
                    ->sortable(),
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
            'index' => ManageWorkScheduleDays::route('/'),
        ];
    }
}
