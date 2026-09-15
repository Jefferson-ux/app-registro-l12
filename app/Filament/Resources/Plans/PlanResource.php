<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;


        public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.multitenancy');
    }

    public static function getModelLabel(): string
    {
        return traductModel('plan');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('plan', plural: true);
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sections.basic_information.title'))
                    ->description(__('sections.basic_information.description'))
                    ->columns([
                        'default' => 1, // Pantallas muy pequeñas (móviles en vertical)
                        //'sm' => 2,      // Pantallas pequeñas (móviles en horizontal / tabletas pequeñas)
                        'md' => 2,      // Pantallas medianas (tabletas estándar)
                        //'lg' => 4,      // Pantallas grandes (monitores de escritorio)
                        //'xl' => 6,      // Pantallas extra grandes
                        //'2xl' => 8,     // Pantallas gigantes
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label(traduct('fields.plan_name'))
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) => 
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                                )
                            ->required(),
                        TextInput::make('slug')
                        ->label(traduct('fields.slug'))
                            ->maxLength(100)
                            ->unique(ignoreRecord:true),
                        Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(3)
                            ->label(traduct('fields.description'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('sections.pricing_and_billing'))
                    ->columns([
                        'default' => 1, // Pantallas muy pequeñas (móviles en vertical)
                        'lg' => 3,      // Pantallas grandes (monitores de escritorio)
                        ])
                    ->schema([
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('S/')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100000)
                            ->label(traduct('fields.price')),
                        TextInput::make('currency')
                            ->required()
                            ->maxLength(3)
                            ->readOnly()
                            ->default('PEN')
                            ->label(traduct('fields.currency')),
                        Select::make('billing_period')
                            ->options(['monthly' => 'Monthly', 'yearly' => 'Yearly'])
                            ->default('monthly')
                            ->required()
                            ->label(traduct('fields.billing_cycle')),
                    ]),
                
                    Section::make()
                        //->description('Deja los campos vacíos o en 0 si no hay restricciones')
                        ->columns([
                            'default' => 1, // Pantallas muy pequeñas (móviles en vertical)
                            'lg' => 3,      // Pantallas grandes (monitores de escritorio)

                            ])
                        ->schema([
                            TextInput::make('max_employees')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(1000)
                                ->label(traduct('fields.max_employees')),
                            TextInput::make('max_users')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(1000)
                                ->label(traduct('fields.max_users')),
                            TextInput::make('max_branches')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(1000)
                                ->label(traduct('fields.max_branches')),

                    ]),
                    Section::make(traduct('fields.plan_status'))
                ->schema([
                    Toggle::make('status')
                        ->label(traduct('fields.status'))
                        ->helperText(__('sections.helpers.status_plan_helper'))
                        ->default(true),
                ]),


            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('max_employees')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_users')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_branches')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('price')
                    ->money(),
                TextEntry::make('currency'),
                TextEntry::make('billing_period')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state)=> match ($state) {
                        'monthly' => Color::hex('#50c878'),
                        'yearly' => Color::hex('#D4AF37'),
                        default => 'gray',
                    }),
                IconEntry::make('status')
                    ->boolean(),
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
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('max_employees')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_users')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_branches')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('billing_period')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => traduct('status.' . $state))
                    ->color(fn (string $state)=> match ($state) {
                        'monthly' => Color::hex('#50c878'),
                        'yearly' => Color::hex('#D4AF37'),
                        default => 'gray',
                    }),
                IconColumn::make('status')
                    ->boolean(),
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
                EditAction::make()
                    ->modalWidth('6xl'),
                DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlans::route('/'),
        ];
    }
}
