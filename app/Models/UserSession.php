<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

class UserSession extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    protected $casts = [
        'abilities'    => 'json',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function isOnline(int $minutos = 5): bool
    {
        return $this->last_used_at !== null
            && $this->last_used_at->gt(now()->subMinutes($minutos));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getEstadoAttribute(): string
    {
        return match (true) {
            $this->isExpired() => 'Expirada',
            $this->isOnline()  => 'En línea',
            default            => 'Inactiva',
        };
    }
}
