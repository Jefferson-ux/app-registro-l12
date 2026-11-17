<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\Auth\RegisterTenant;
use App\Filament\App\Pages\EditProfile;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeamId;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\App\Pages\AppDashboard;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->font('Bricolage Grotesque')
            ->registration(RegisterTenant::class)
            ->brandLogo(fn() => view('filament.app.brand'))
            ->profile(EditProfile::class)
            ->passwordReset()
            ->colors([
                'primary' => "#0ea5e9",
                'danger' => Color::Red,
                'gray' => Color::Zinc,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'indigo' => Color::Indigo,
                'teal' => Color::Teal
            ])
            ->assets([
                // Registras tu archivo CSS plano directamente
                Css::make('custom-table-styles', asset('css/app/custom/custom-table.css')),
                Css::make('theme-loader', asset('css/app/ui/theme-loader.css')),
                Css::make('dashboard-styles', asset('css/app/custom/dashboard-styles.css')),
                Css::make('custom-sidebar-styles', asset('css/app/custom/custom-sidebar.css')),
                Css::make('custom-scroll-styles', asset('css/app/custom/custom-scroll.css')),
                Js::make('theme-loader', asset('js/app/ui/theme-loader.js')),
            ])

            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                AppDashboard::class,
            ])

            ->discoverWidgets(in: app_path('Filament/Widgets/Attendance'), for: 'App\Filament\Widgets\Attendance')
            ->widgets([])
            ->navigationGroups([
                NavigationGroup::make(fn() => traduct('navigation.business'))
                    ->collapsed(true),
                NavigationGroup::make(fn() => traduct('navigation.attendance'))
                    ->collapsed(true),
                NavigationGroup::make(fn() => traduct('navigation.hr'))
                    ->collapsed(true),
                NavigationGroup::make(fn() => traduct('navigation.access_control'))
                    ->collapsed(true),

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,

                // Middleware de Cambio de Idioma
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetPermissionsTeamId::class,
            ], isPersistent: true)
            ->userMenuItems([
                'toggle_lang' => Action::make('toggle_lang')
                    ->label(fn() => app()->getLocale() === 'es' ? 'Switch to English' : 'Cambiar a Español')
                    ->icon('heroicon-o-language')
                    ->url(fn() => route('lang.switch', app()->getLocale() === 'es' ? 'en' : 'es')),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn() => view('filament.hooks.logout-confirm-modal'),
            );
    }
    public function boot(): void
    {
        Tab::configureUsing(fn(Tab $tab) => $tab->columns([
            'default' => 1,  // móvil
            'md'      => 2,  // pantallas medianas
            'lg'      => 3,  // pantallas grandes
        ]));
        ViewAction::configureUsing(
            fn(ViewAction $action) => $action->modalWidth(Width::SixExtraLarge)
        );
        CreateAction::configureUsing(
            fn(CreateAction $action) => $action->modalWidth(Width::SixExtraLarge)
        );

        EditAction::configureUsing(
            fn(EditAction $action) => $action->modalWidth(Width::SixExtraLarge)
        );
    }
}
