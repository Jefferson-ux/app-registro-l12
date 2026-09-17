<?php
namespace App\Filament\App\Pages\Auth;

use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Schemas\Schema; 
use Filament\Pages\SimplePage;
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
                'md'=> 2
            ])
            ->components([ 
                TextInput::make('name')
                    ->label('Tenant Name')
                    ->required()
                    ->hint('(Obligatorio)')
                    ->hintIcon(Heroicon::ExclamationTriangle)
                    ->prefixIcon('heroicon-m-building-office')
                    ->maxLength(150)
                    ->label(traduct('fields.name'))
                    ->autofocus(),
                TextInput::make('business_name')
                    ->label(traduct('fields.business_name'))
                    ->default(null)
                    ->suffixIcon('heroicon-m-building-office-2')
                    ->hint('(Opcional)')
                    ->hintIcon(Heroicon::QuestionMarkCircle)
                    ->maxLength(200),
                TextInput::make('tax_id')
                    ->label(traduct('fields.tax_id'))
                    ->default(null)
                    ->prefixIcon('heroicon-m-identification')
                    ->hint('(Opcional)')
                    ->hintIcon(Heroicon::QuestionMarkCircle)
                    ->maxLength(50),
                TextInput::make('email')
                    ->label(traduct('fields.email'))
                    ->email()
                    ->suffixIcon('heroicon-m-envelope')
                    ->hint('(Opcional)')
                    ->hintIcon(Heroicon::QuestionMarkCircle)
                    ->maxLength(150)
                    ->default(null),
                TextInput::make('phone')
                    ->label(traduct('fields.phone'))
                    ->tel()
                    ->suffixIcon('heroicon-m-phone')
                    ->hint('(Opcional)')
                    ->hintIcon(Heroicon::QuestionMarkCircle)
                    ->maxLength(50)
                    ->default(null),

Select::make('country')
    ->label('País')
    ->options([
        'PE' => 'Perú',
        'ES' => 'España',
    ])
    ->live(), // Hace que el formulario reaccione al cambio

Select::make('timezone')
    ->label('Zona Horaria')
    ->options(fn (Get $get) => match ($get('country')) {
        'PE' => ['America/Lima' => 'Lima (GMT-5)'],
        'ES' => ['Europe/Madrid' => 'Madrid (GMT+1)', 'Atlantic/Canary' => 'Canarias (GMT+0)'],
        default => ['UTC' => 'UTC'],
    })


            ])
            ->statePath('data');
    }

public function register(): void
{
    $data = $this->form->getState();

    try {
        DB::transaction(function () use ($data) {
            
            $tenant = Tenant::create([
                'name'          => $data['name'], 
                'business_name' => $data['business_name'] ?? null,
                'tax_id'        => $data['tax_id'] ?? null,
                'email'         => $data['email'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'country'       => $data['country'] ?? null,
                'timezone'      => $data['timezone'] ?? null,
            ]);

            // 2. Vincular al usuario autenticado actual con su nuevo Tenant
            $user = Auth::user();
            
            if ($user) {
                $user->update([
                    'tenant_id' => $tenant->id,
                ]);
            }
        });

        //Notification::make()
        //    ->title('Empresa registrada con éxito')
        //    ->success()
        //    ->send();

        $this->redirect(route('thank-you'));
        //$this->redirect(filament()->getUrl());

    } catch (\Throwable $th) {
        Notification::make()
            ->title('Error al registrar la empresa')
            ->body('Ocurrió un problema durante el registro. Por favor, intenta de nuevo.')
            ->danger()
            ->send();
    }
}
}