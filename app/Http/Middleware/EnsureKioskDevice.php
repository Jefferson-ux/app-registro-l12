<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\DeviceType;
use App\Models\Device;

class EnsureKioskDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->user();

        if (! $device instanceof Device || $device->type !== DeviceType::Kiosk) {
            return response()->json([
                'success' => false,
                'message' => 'El token no pertenece a un kiosco.',
            ], 403);
        }

        $token = $device->currentAccessToken();
        if (! $token || ! $token->can('kiosk')) {
            return response()->json([
                'success' => false,
                'message' => 'El token no tiene permisos de kiosco.',
            ], 403);
        }

        $device->last_used_at = now();
        $device->last_ip = $request->ip();
        $device->saveQuietly();

        return $next($request);
    }
}
