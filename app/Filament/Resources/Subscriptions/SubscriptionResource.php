<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Resources\Subscriptions\Pages\ManageSubscriptions;
use App\Models\Subscription;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
->components([
            Section::make('Información de la Suscripción')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('tenant_id')
                        ->label('Empresa (Tenant)')
                        ->relationship('tenant', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('plan_id')
                        ->label('Plan Asignado')
                        ->relationship('plan', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            'trial' => 'Prueba (Trial)',
                            'active' => 'Activa',
                            'past_due' => 'Pago Pendiente',
                            'cancelled' => 'Cancelada',
                            'expired' => 'Expirada',
                        ])
                        ->default('trial')
                        ->required()
                        ->native(false),
                ]),

            Section::make('Vigencia y Cancelación')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Fecha de Inicio')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),

                    DateTimePicker::make('ends_at')
                        ->label('Fecha de Vencimiento')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),

                    DateTimePicker::make('cancelled_at')
                        ->label('Fecha de Cancelación')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tenant.name')
                    ->label('Tenant'),
                TextEntry::make('plan.name')
                    ->label('Plan'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'past_due' => 'warning',
                        'cancelled', 'expired' => 'danger',
                        default => 'gray',
                        }),
                TextEntry::make('starts_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('cancelled_at')
                    ->dateTime()
                    ->placeholder('-'),
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
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'past_due' => 'warning',
                        'cancelled', 'expired' => 'danger',
                        default => 'gray',
                        })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activa',
                        'trial' => 'Prueba (Trial)',
                        'past_due' => 'Pago Pendiente',
                        'cancelled' => 'Cancelada',
                        'expired' => 'Expirada',
                        default => $state,
                        }),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->dateTime('d/m/Y H:i')
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
            'index' => ManageSubscriptions::route('/'),
        ];
    }
}
