<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateLastLoginAt
{

    public function handle(Login $event): void
    {
        // $event->user es el registro del usuario que acaba de iniciar sesión
        $event->user->timestamps = false; // Opcional: evita modificar la columna updated_at
        
        $event->user->update([
            'last_login_at' => now(),
        ]);
    }
}
