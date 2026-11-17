<?php

namespace App\Filament\App\Resources\Holidays;

use App\Filament\App\Resources\Holidays\Pages\ManageHolidays;
use App\Models\Holiday;
use App\Traits\ScopesTenantResource;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\utils\Regex;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class HolidayResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?string $recordTitleAttribute = 'name';
    public static bool $shouldRegisterGlobalSearch = false;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.hr');
    }

    public static function getModelLabel(): string
    {
        return traductModel('holiday');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('holiday', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->default(null)
                    ->label(traduct('fields.branch')),
                TextInput::make('name')
                    ->required()
                    ->maxValue(150)
                    ->regex(Regex::PHRASE)
                    ->label(traduct('fields.holiday_name')),
                DatePicker::make('holiday_date')
                    ->required()
                    ->label(traduct('fields.holiday_date'))
                    ->displayFormat('d/m/Y')
                    ->native(false),
                Toggle::make('is_paid')
                    ->required()
                    ->default(true)
                    ->label(traduct('fields.is_paid')),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Feriado')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(traduct('sections.general'))
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->schema([
                                TextEntry::make('branch.name')
                                    ->placeholder(__('empty.branch'))
                                    ->label(traduct('fields.branch')),
                                TextEntry::make('name')
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.holiday_name')),
                                TextEntry::make('holiday_date')
                                    ->date()
                                    ->placeholder(__('empty.date'))
                                    ->label(traduct('fields.holiday_date')),
                                IconEntry::make('is_paid')
                                    ->boolean()
                                    ->label(traduct('fields.is_paid')),
                            ]),

                        Tab::make(traduct('sections.audit'))
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.created_at')),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.updated_at')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('branch.name')
                    ->searchable()
                    ->placeholder('-')
                    ->label(traduct('fields.branch')),
                TextColumn::make('name')
                    ->searchable()
                    ->label(traduct('fields.holiday_name')),
                TextColumn::make('holiday_date')
                    ->date()
                    ->sortable()
                    ->label(traduct('fields.holiday_date')),
                IconColumn::make('is_paid')
                    ->boolean()
                    ->label(traduct('fields.is_paid')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct('fields.created_at')),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct('fields.updated_at')),
            ])
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->modalHeading(__('modals.force_delete.heading'))
                    ->modalDescription(__('modals.force_delete.description'))
                    ->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading(__('modals.bulk.force_delete.heading'))
                        ->modalDescription(__('modals.bulk.force_delete.description')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHolidays::route('/'),
        ];
    }
}
