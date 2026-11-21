<?php

namespace App\Filament\App\Resources\Devices;

use App\Enums\DeviceType;
use App\Filament\App\Resources\Devices\Pages\ManageDevices;
use App\Models\Branch;
use App\Models\Device;
use App\Traits\ScopesTenantResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class DeviceResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = Device::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.access_control');
    }

    public static function getModelLabel(): string
    {
        return __('models.device.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models.device.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('fields.name'))
                    ->unique(
                        table: 'devices',
                        ignoreRecord: true,
                        modifyRuleUsing: fn(Unique $rule): Unique => $rule
                            ->where('tenant_id', auth()->user()?->tenant_id),
                    ),
                Select::make('branch_id')
                    ->label(__('fields.branch'))
                    ->required(fn(?Device $record) => $record === null)
                    ->options(fn() => Branch::query()
                        ->where('tenant_id', auth()->user()?->tenant_id)
                        ->pluck('name', 'id'))
                    ->rules(fn() => [
                        Rule::exists('branches', 'id')
                            ->where('tenant_id', auth()->user()?->tenant_id),
                    ]),
                Select::make('type')
                    ->label(__('fields.type'))
                    ->options([
                        DeviceType::Kiosk->value => DeviceType::Kiosk->getLabel(),
                    ])
                    ->default(DeviceType::Kiosk->value)
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('DeviceTabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(traduct('fields.general'))
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('fields.name')),
                                TextEntry::make('type')
                                    ->label(__('fields.type'))
                                    ->badge()
                                    ->formatStateUsing(fn(DeviceType $state): string => $state->getLabel())
                                    ->color('primary'),
                                TextEntry::make('branch.name')
                                    ->label(__('fields.branch'))
                                    ->placeholder('-'),
                            ]),

                        Tab::make(__('fields.status'))
                            ->schema([
                                TextEntry::make('is_active')
                                    ->label(__('fields.status'))
                                    ->badge()
                                    ->formatStateUsing(fn(Device $record): string => ! $record->is_active
                                        ? __('fields.disconnected')
                                        : ($record->is_online ? __('fields.online') : __('fields.offline')))
                                    ->color(fn(Device $record): string => ! $record->is_active
                                        ? 'gray'
                                        : ($record->is_online ? 'success' : 'warning')),
                                TextEntry::make('is_online')
                                    ->label(__('fields.online'))
                                    ->badge()
                                    ->formatStateUsing(fn(bool $state): string => $state ? __('fields.online') : __('fields.offline'))
                                    ->color(fn(bool $state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('last_used_at')
                                    ->label(__('fields.last_used_at'))
                                    ->since()
                                    ->placeholder('-'),
                            ]),

                        Tab::make(traduct('fields.telemetry_and_meta'))
                            ->schema([
                                TextEntry::make('latitude')
                                    ->label(traduct('fields.latitude'))
                                    ->placeholder('-'),
                                TextEntry::make('longitude')
                                    ->label(traduct('fields.longitude'))
                                    ->placeholder('-'),
                                TextEntry::make('device_identifier')
                                    ->label(traduct('fields.device_identifier'))
                                    ->copyable()
                                    ->placeholder('-'),
                                TextEntry::make('last_ip')
                                    ->label(traduct('fields.last_ip'))
                                    ->copyable()
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->label(traduct('fields.created_at'))
                                    ->dateTime()
                                    ->placeholder('-'),
                                TextEntry::make('updated_at')
                                    ->label(traduct('fields.updated_at'))
                                    ->dateTime()
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('name')
                    ->label(__('fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label(__('fields.branch'))
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('fields.type'))
                    ->badge()
                    ->formatStateUsing(fn(DeviceType $state): string => $state->getLabel())
                    ->color('primary'),
                TextColumn::make('is_active')
                    ->label(__('fields.status'))
                    ->badge()
                    ->formatStateUsing(fn(Device $record): string => ! $record->is_active
                        ? __('fields.disconnected')
                        : ($record->is_online ? __('fields.online') : __('fields.offline')))
                    ->color(fn(Device $record): string => ! $record->is_active
                        ? 'gray'
                        : ($record->is_online ? 'success' : 'warning')),
                TextColumn::make('last_used_at')
                    ->label(__('fields.last_used_at'))
                    ->since()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->modalFooterActions([
                        Action::make('deactivate')
                            ->label(__('actions.deactivate_kiosk'))
                            ->color('danger')
                            ->visible(fn(Device $record): bool => $record->is_active && $record->tokens()->exists())
                            ->requiresConfirmation()
                            ->action(fn(Device $record) => $record->disconnect()),

                        Action::make('regenerateToken')
                            ->label(__('actions.regenerate_token'))
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading(__('devices.regenerate_heading'))
                            ->modalDescription(__('devices.regenerate_description'))
                            ->action(function (Device $record, $livewire): void {
                                $record->disconnect();
                                $livewire->showGeneratedToken($record->issueToken());
                            }),
                    ]),
                DeleteAction::make()
                    ->requiresConfirmation(),
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
            'index' => ManageDevices::route('/'),
        ];
    }
}
