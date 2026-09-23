<?php

namespace App\Providers;

use App\Listeners\UpdateLastLoginAt;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
            Gate::before(function ($user, $ability) {
        return $user->email === config('app.super_admin_email') ? true : null;
            });
        Event::listen(
            Login::class,
            UpdateLastLoginAt::class
        );
    }
}
