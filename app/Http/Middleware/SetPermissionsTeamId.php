<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionsTeamId
{

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;

        if (auth()->check()) {
            $tenantId = auth()->user()->tenant_id;

            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        }

        return $next($request);
    }
}
