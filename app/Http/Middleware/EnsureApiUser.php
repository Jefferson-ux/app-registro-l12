<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() instanceof Device) {
            return response()->json([
                'success' => false,
                'message' => 'Este token no puede acceder a esta ruta.',
            ], 403);
        }

        return $next($request);
    }
}
