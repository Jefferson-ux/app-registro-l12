<?php

namespace App\Filament\App\Pages\Auth;

use App\Actions\RegisterTenantAction;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Schemas\Schema;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterTenant extends SimplePage implements HasForms
{
    use InteractsWithForms;

    // Ruta donde carga la vista
    protected string $view = 'filament.app.pages.auth.register-tenant';

    protected Width|string|null $maxWidth = Width::ThreeExtraLarge;

    public ?array $data = [];

    public function mount(): void
    {
        if (Auth::check()) {
            redirect()->intended(filament()->getUrl());
        }

        $this->form->fill();
    }



    public function form(Schema $schema): Schema
    {
return $schema
        ->columns([
            'default' => 1,
            'md'      => 2,
        ])
        ->components([
            // 1. Nombre Comercial (Obligatorio - Ocupa ancho completo)
            TextInput::make('name')
                ->label(traduct('fields.name'))
                ->required()
                ->hint('(Obligatorio)')
                ->hintIcon(Heroicon::ExclamationTriangle)
                ->prefixIcon('heroicon-m-building-office')
                ->maxLength(150)
                ->autofocus()
                ->columnSpanFull(),

            // 2. Razon Social y RUC/TaxID (Lado a lado en MD)
            TextInput::make('business_name')
                ->label(traduct('fields.business_name'))
                ->hint('(Opcional)')
                ->hintIcon(Heroicon::QuestionMarkCircle)
                ->suffixIcon('heroicon-m-building-office-2')
                ->maxLength(200)
                ->default(null),

            TextInput::make('tax_id')
                ->label(traduct('fields.tax_id'))
                ->hint('(Opcional)')
                ->hintIcon(Heroicon::QuestionMarkCircle)
                ->prefixIcon('heroicon-m-identification')
                ->maxLength(50)
                ->default(null),

            // 3. Email y Teléfono (Lado a lado en MD)
            TextInput::make('email')
                ->label(traduct('fields.email'))
                ->email()
                ->required()
                ->prefixIcon('heroicon-m-envelope')
                ->hint('(Obligatorio)')
                ->hintIcon(Heroicon::ExclamationTriangle)
                ->maxLength(150)
                ->default(null),

            TextInput::make('phone')
                ->label(traduct('fields.phone'))
                ->tel()
                ->hint('(Opcional)')
                    ->hintIcon(Heroicon::QuestionMarkCircle)
                ->prefixIcon('heroicon-m-phone')
                ->maxLength(50)
                ->default(null),

            // 4. Logo a la izquierda (ocupa 1 columna)
                FileUpload::make('logo')
                ->label(traduct('fields.logo'))
                ->image()
                ->imageEditor()
                ->directory('tenants/logos')
                ->hint('(Opcional)')
                    ->hintIcon(Heroicon::QuestionMarkCircle)
                ->columnSpan(1),

            // 5. Grupo a la derecha apilando País y Zona Horaria en 2 filas
            Group::make([
                Select::make('country')
                    ->label(traduct('fields.country'))
                    ->prefixIcon('heroicon-m-globe-americas')
                    ->hint('(Obligatorio)')
                    ->hintIcon(Heroicon::ExclamationTriangle)
                    ->options([
                        'PE' => 'Perú',
                        'ES' => 'España',
                    ])
                    ->required()
                    ->live(),

                Select::make('timezone')
                    ->label(traduct('fields.timezone'))
                    ->prefixIcon('heroicon-m-clock')
                    ->hint('(Obligatorio)')
                    ->required()
                    ->hintIcon(Heroicon::ExclamationTriangle)
                    ->options(fn (Get $get) => match ($get('country')) {
                        'PE' => ['America/Lima' => 'Lima (GMT-5)'],
                        'ES' => ['Europe/Madrid' => 'Madrid (GMT+1)', 'Atlantic/Canary' => 'Canarias (GMT+0)'],
                        default => ['UTC' => 'UTC'],
                    }),
])
->columns(1)
->columnSpan(1),
        ])
        ->statePath('data');
    }

    public function register(RegisterTenantAction $action): void
    {
        $data = $this->form->getState();

        try {

            $action->execute($data);

            $this->redirect(route('thank-you'));
        } catch (\Throwable $th) {
            /*Notification::make()
                ->title('Error al registrar la empresa')
                ->body('Ocurrió un problema durante el registro. Por favor, intenta de nuevo.')
                ->danger()
                ->send();*/
            Notification::make()
                ->title('Error al registrar la empresa')
                ->body("Error: {$th->getMessage()} en {$th->getFile()}:{$th->getLine()}")
                ->danger()
                ->send();
        }
    }
}
