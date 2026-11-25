<?php

namespace App\Filament\App\Resources\AuditLogs;

use App\Filament\App\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = 'navigation.auditoria';

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.auditoria');
    }

    public static function getModelLabel(): string
    {
        return traductModel('audit_log');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('audit_log', plural: true);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(traduct('sections.general'))
                            ->columns(2)
                            ->schema([
                                TextEntry::make('tenant.name')
                                    ->label(traduct('fields.tenant'))
                                    ->placeholder('-'),
                                TextEntry::make('user.name')
                                    ->label(traduct('fields.user'))
                                    ->placeholder('-'),
                                TextEntry::make('action')
                                    ->label(traduct('fields.action'))
                                    ->badge()
                                    ->color(fn(string $state): string => match (true) {
                                        str_ends_with($state, '.created')     => 'success',
                                        str_ends_with($state, '.updated')     => 'info',
                                        str_ends_with($state, '.soft_delete') => 'warning',
                                        str_ends_with($state, '.deleted')     => 'danger',
                                        str_ends_with($state, '.restored')    => 'primary',
                                        default                               => 'gray',
                                    })
                                    ->formatStateUsing(function (string $state): string {
                                        [$modelKey, $event] = array_pad(explode('.', $state, 2), 2, null);

                                        $modelLabel = $modelKey ? traductModel($modelKey) : '';
                                        $eventLabel = $event ? (__("audit.events.{$event}") !== "audit.events.{$event}"
                                            ? __("audit.events.{$event}")
                                            : Str::headline($event)) : '';

                                        return trim("{$modelLabel} · {$eventLabel}", ' ·');
                                    }),

                                TextEntry::make('entity_type')
                                    ->label(traduct('fields.entity_type'))
                                    ->formatStateUsing(fn(string $state): string => traductModel(Str::snake($state))),
                                TextEntry::make('entity_id')
                                    ->label(traduct('fields.entity_id'))
                                    ->numeric()
                                    ->placeholder('-'),
                                TextEntry::make('ip_address')
                                    ->label(traduct('fields.ip_address'))
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->label(traduct('fields.created_at'))
                                    ->dateTime()
                                    ->dateTime('l d/m/Y H:i')
                                    ->placeholder('-'),
                            ]),
                        Tab::make(traduct('sections.audit'))
                            ->columns(2)
                            ->schema([
                                KeyValueEntry::make('old_values')
                                    ->label(traduct('fields.old_values'))
                                    ->placeholder('-'),
                                KeyValueEntry::make('new_values')
                                    ->label(traduct('fields.new_values'))
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(traduct('fields.user'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(traduct('fields.action'))
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_ends_with($state, '.created')     => 'success',
                        str_ends_with($state, '.updated')     => 'info',
                        str_ends_with($state, '.soft_delete') => 'warning',
                        str_ends_with($state, '.deleted')     => 'danger',
                        str_ends_with($state, '.restored')    => 'primary',
                        default                               => 'gray',
                    })
                    ->formatStateUsing(function (string $state): string {
                        [$modelKey, $event] = array_pad(explode('.', $state, 2), 2, null);

                        $modelLabel = $modelKey ? traductModel($modelKey) : '';
                        $eventLabel = $event ? (__("audit.events.{$event}") !== "audit.events.{$event}"
                            ? __("audit.events.{$event}")
                            : Str::headline($event)) : '';

                        return trim("{$modelLabel} · {$eventLabel}", ' ·');
                    }),

                TextColumn::make('entity_type')
                    ->label(traduct('fields.entity_type'))
                    ->searchable()
                    ->formatStateUsing(fn(string $state): string => traductModel(Str::snake($state))),
                TextColumn::make('entity_id')
                    ->label(traduct('fields.entity_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label(traduct('fields.ip_address'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(traduct('fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuditLogs::route('/'),
        ];
    }
}
