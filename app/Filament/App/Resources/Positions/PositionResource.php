<?php

namespace App\Filament\App\Resources\Positions;

use App\Filament\App\Resources\Positions\Pages\ManagePositions;
use App\Models\Position;
use App\Traits\ScopesTenantResource;
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
use Filament\Forms\Components\Textarea;
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

class PositionResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = Position::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.business');
    }

    public static function getModelLabel(): string
    {
        return traductModel('position');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('position', plural: true);
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->default(null)
                    ->label(traduct("fields.department")),
                TextInput::make('name')
                    ->required()
                    ->maxLength(150)
                    ->label(traduct("fields.position_name")),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull()
                    ->maxLength(65535)
                    ->label(traduct("fields.description")),
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
                TextEntry::make('department.name')
                    ->placeholder('-')
                    ->label(traduct("fields.department")),
                TextEntry::make('name')
                    ->label(traduct("fields.position_name")),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->label(traduct("fields.description")),
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
                    ->visible(fn (Position $record): bool => $record->trashed())
                    ->label(traduct("fields.deleted_at")),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('department.name')
                    ->searchable()
                    ->label(traduct("fields.department")),
                TextColumn::make('name')
                    ->searchable()
                    ->label(traduct("fields.position_name")),
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
            'index' => ManagePositions::route('/'),
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
