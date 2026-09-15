<?php

namespace App\Filament\App\Resources\Branches;

use App\Filament\App\Resources\Branches\Pages\ManageBranches;
use App\Models\Branch;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.business');
    }

    public static function getModelLabel(): string
    {
        return traductModel('branch');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('branch', plural: true);
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label(traduct("fields.tenant"))
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(150)
                    ->label(traduct("fields.branch_name")),
                TextInput::make('address')
                    ->default(null)
                    ->maxLength(255)
                    ->label(traduct("fields.address")),
                TextInput::make('latitude')
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-90)
                    ->maxValue(90)
                    ->nullable()
                    ->label(traduct("fields.latitude")),
                TextInput::make('longitude')
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-180)
                    ->maxValue(180)
                    ->nullable()
                    ->label(traduct("fields.longitude")),
                TextInput::make('allowed_radius')
                    ->numeric()
                    ->default(null)
                    ->maxValue(10000000)
                    ->label(traduct("fields.allowed_radius")),
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
                    ->label('Tenant')
                    ->label(traduct("fields.tenant")),
                TextEntry::make('name')
                    ->label(traduct("fields.branch_name")),
                TextEntry::make('address')
                    ->placeholder('-')
                    ->label(traduct("fields.adress")),
                TextEntry::make('latitude')
                    ->numeric()
                    ->placeholder('-')
                    ->label(traduct("fields.latitude")),
                TextEntry::make('longitude')
                    ->numeric()
                    ->placeholder('-')
                    ->label(traduct("fields.longitude")),
                TextEntry::make('allowed_radius')
                    ->numeric()
                    ->placeholder('-')
                    ->label(traduct("fields.allowed_radius")),
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
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Branch $record): bool => $record->trashed())
                    ->label(traduct("fields.deleted_at")),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(traduct("fields.branch_name")),
                TextColumn::make('address')
                    ->searchable()
                    ->label(traduct("fields.address")),
                TextColumn::make('latitude')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.latitude")),
                TextColumn::make('longitude')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.longitude")),
                TextColumn::make('allowed_radius')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.allowed_radius")),
                IconColumn::make('status')
                    ->boolean()
                    ->label(traduct("fields.status")),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.created")),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.updated_at")),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.deleted_at")),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBranches::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
