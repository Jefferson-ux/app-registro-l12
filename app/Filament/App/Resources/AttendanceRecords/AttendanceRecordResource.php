<?php

namespace App\Filament\App\Resources\AttendanceRecords;

use App\Filament\App\Resources\AttendanceRecords\Pages\ManageAttendanceRecords;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'id';

    
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.attendance');
    }

    public static function getModelLabel(): string
    {
        return traductModel('attendance_record');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('attendance_record', plural: true);
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
                    ->label(traduct('fields.employee')),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->default(null)
                    ->label(traduct('fields.branch')),
                Select::make('type')
                    ->options([
                        'check_in' => traductShort('status.record_type.check_in'),
                        'check_out' => traductShort('status.record_type.check_out'),
                        'break_start' => traductShort('status.record_type.break_start'),
                        'break_end' => traductShort('status.record_type.break_end'),
                    ])
                    ->required()
                    ->label(traduct('fields.record_type')),
                DateTimePicker::make('recorded_at')
                    ->required()
                    ->label(traduct('fields.recorded_at')),
                Select::make('method')
                    ->options([
                        'web' => traductShort('status.method.web'),
                        'mobile' => traductShort('status.method.mobile'),
                        'kiosk' => traductShort('status.method.kiosk'),
                        'qr' => traductShort('status.method.qr'),
                        'manual' => traductShort('status.method.manual'),
                        'biometric' => traductShort('status.method.biometric'),
                        'api' => traductShort('status.method.api'),
                    ])
                    ->required()
                    ->label(traduct('fields.method')),
                TextInput::make('latitude')
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-90)
                    ->maxValue(90)
                    ->nullable()
                    ->label(traduct('fields.latitude')),
                TextInput::make('longitude')
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-180)
                    ->maxValue(180)
                    ->nullable()
                    ->label(traduct('fields.longitude')),
                TextInput::make('ip_address')
                    ->default(null)
                    ->maxValue(45)
                    ->label(traduct('fields.ip_address')),
                TextInput::make('device_identifier')
                    ->default(null)
                    ->maxValue(255)
                    ->label(traduct('fields.device_identifier')),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull()
                    ->maxLength(65535)
                    ->label(traduct('fields.notes')),
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
            TextEntry::make('branch.name')
                ->label(traduct('fields.branch'))
                ->placeholder('-'),
            TextEntry::make('type')
                ->label(traduct('fields.record_type'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'check_in' => traductShort('status.record_type.check_in'),
                    'check_out' => traductShort('status.record_type.check_out'),
                    'break_start' => traductShort('status.record_type.break_start'),
                    'break_end' => traductShort('status.record_type.break_end'),
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'check_in' => 'success',
                    'check_out' => 'danger',
                    'break_start', 'break_end' => 'info',
                    default => 'gray',
                }),
            TextEntry::make('recorded_at')
                ->label(traduct('fields.recorded_at'))
                ->dateTime(),
            TextEntry::make('method')
                ->label(traduct('fields.method'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'web' => traductShort('status.method.web'),
                    'mobile' => traductShort('status.method.mobile'),
                    'kiosk' => traductShort('status.method.kiosk'),
                    'qr' => traductShort('status.method.qr'),
                    'manual' => traductShort('status.method.manual'),
                    'biometric' => traductShort('status.method.biometric'),
                    'api' => traductShort('status.method.api'),
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'web', 'mobile' => 'info',
                    'kiosk', 'qr' => 'gray',
                    'manual' => 'warning',
                    'biometric', 'api' => 'success',
                    default => 'gray',
                }),
            TextEntry::make('latitude')
                ->label(traduct('fields.latitude'))
                ->numeric()
                ->placeholder('-'),
            TextEntry::make('longitude')
                ->label(traduct('fields.longitude'))
                ->numeric()
                ->placeholder('-'),
            TextEntry::make('ip_address')
                ->label(traduct('fields.ip_address'))
                ->placeholder('-'),
            TextEntry::make('device_identifier')
                ->label(traduct('fields.device_identifier'))
                ->placeholder('-'),
            TextEntry::make('notes')
                ->label(traduct('fields.notes'))
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
            TextColumn::make('type')
                ->label(traduct('fields.record_type'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'check_in' => traductShort('status.record_type.check_in'),
                    'check_out' => traductShort('status.record_type.check_out'),
                    'break_start' => traductShort('status.record_type.break_start'),
                    'break_end' => traductShort('status.record_type.break_end'),
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'check_in' => 'success',
                    'check_out' => 'danger',
                    'break_start', 'break_end' => 'info',
                    default => 'gray',
                }),
            TextColumn::make('recorded_at')
                ->label(traduct('fields.recorded_at'))
                ->dateTime()
                ->sortable(),
            TextColumn::make('branch.name')
                ->label(traduct('fields.branch'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('method')
                ->label(traduct('fields.method'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'web' => traductShort('status.method.web'),
                    'mobile' => traductShort('status.method.mobile'),
                    'kiosk' => traductShort('status.method.kiosk'),
                    'qr' => traductShort('status.method.qr'),
                    'manual' => traductShort('status.method.manual'),
                    'biometric' => traductShort('status.method.biometric'),
                    'api' => traductShort('status.method.api'),
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'web', 'mobile' => 'info',
                    'kiosk', 'qr' => 'gray',
                    'manual' => 'warning',
                    'biometric', 'api' => 'success',
                    default => 'gray',
                })
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('latitude')
                ->label(traduct('fields.latitude'))
                ->numeric()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('longitude')
                ->label(traduct('fields.longitude'))
                ->numeric()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('ip_address')
                ->label(traduct('fields.ip_address'))
                ->searchable(),
            TextColumn::make('device_identifier')
                ->label(traduct('fields.device_identifier'))
                ->searchable()
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
            'index' => ManageAttendanceRecords::route('/'),
        ];
    }
}
