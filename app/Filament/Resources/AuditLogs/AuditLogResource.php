<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'administration';

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.administration');
    }

    public static function getModelLabel(): string
    {
        return traduct('fields.audit_log');
    }

    public static function getPluralModelLabel(): string
    {
        return traduct('fields.audit_logs');
    }



    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant.name')
                    ->label(__('fields.tenant')),
                TextEntry::make('user.name')
                    ->label(__('fields.user')),
                TextEntry::make('action')
                    ->label(__('fields.action')),
                TextEntry::make('entity_type')
                    ->label(__('fields.entity_type')),
                TextEntry::make('entity_id')
                    ->numeric()
                    ->placeholder('-')
                    ->label(__('fields.entity_id')),
                TextEntry::make('old_values')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->label(__('fields.old_values')),
                TextEntry::make('new_values')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->label(__('fields.new_values')),
                TextEntry::make('ip_address')
                    ->placeholder('-')
                    ->label(__('fields.ip_address')),
                TextEntry::make('user_agent')
                    ->placeholder('-')
                    ->label(__('fields.user_agent')),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(__('fields.created_at')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable()
                    ->sortable()
                    ->label(traduct('fields.tenant')),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label(traduct('fields.user')),
                TextColumn::make('action')
                    ->searchable()
                    ->label(traduct('fields.action')),
                TextColumn::make('entity_type')
                    ->searchable()
                    ->label(traduct('fields.entity_type')),
                TextColumn::make('entity_id')
                    ->numeric()
                    ->sortable()
                    ->label(traduct('fields.entity_id')),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->label(traduct('fields.ip_address')),
                TextColumn::make('user_agent')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                            $state = $column->getState();
                            if (strlen($state) <= $column->getCharacterLimit()) {
                                return null;
                            }
                            return $state; // texto completo en el tooltip
                        })
                    ->label(traduct('fields.user_agent')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct('fields.created_at')),
            ])
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
