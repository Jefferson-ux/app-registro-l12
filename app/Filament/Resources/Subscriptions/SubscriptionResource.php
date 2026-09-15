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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

        public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.multitenancy');
    }

    public static function getModelLabel(): string
    {
        return traductModel('subscription');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('subscription', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
->components([
            Section::make(traduct('sections.subscription_info.title'))
                ->description(traduct('sections.subscription_info.description'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('tenant_id')
                        ->label(traduct('fields.tenant'))
                        ->relationship('tenant', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('plan_id')
                        ->label(traduct('fields.plan'))
                        ->relationship('plan', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('status')
                        ->label(traduct('fields.plan_status'))
                        ->options([
                            'trial' => traduct('status.trial'),
                            'active' => traduct('status.active'),
                            'past_due' => traduct('status.past_due'),
                            'cancelled' => traduct('status.cancelled'),
                            'expired' => traduct('status.expired'),
                        ])
                        ->default('trial')
                        ->required()
                        ->native(false),
                ]),

            Section::make(traduct('sections.validity_cancellation.title'))
                ->description(traduct('sections.validity_cancellation.description'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label(traduct('fields.starts_at'))
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),

                    DateTimePicker::make('ends_at')
                        ->label(traduct('fields.ends_at'))
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),

                    DateTimePicker::make('cancelled_at')
                        ->label(traduct('fields.cancelled_at'))
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
                    ->label(traduct('fields.tenant')),
                TextEntry::make('plan.name')
                    ->label(traduct('fields.plan')),
                TextEntry::make('status')
                    ->label(traduct('fields.plan_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'past_due' => 'warning',
                        'cancelled', 'expired' => 'danger',
                        default => 'gray',
                        }),
                TextEntry::make('starts_at')
                    ->label(traduct('fields.starts_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->label(traduct('fields.ends_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('cancelled_at')
                    ->label(traduct('fields.cancelled_at'))
                    ->dateTime()
                    ->placeholder('-'),
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
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('tenant.name')
                    ->label(traduct('fields.tenant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label(traduct('fields.plan'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(traduct('fields.plan_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'past_due' => 'warning',
                        'cancelled', 'expired' => 'danger',
                        default => 'gray',
                        }),
                TextColumn::make('starts_at')
                    ->label(traduct('fields.starts_at'))    
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(traduct('fields.ends_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->label(traduct('fields.cancelled_at'))
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(traduct('fields.created_at'))
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
            'index' => ManageSubscriptions::route('/'),
        ];
    }
}
