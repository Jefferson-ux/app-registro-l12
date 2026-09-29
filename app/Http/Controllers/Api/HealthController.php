<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index()
    {
        return response()->json([

            "status" => 200,
            "success" => true,
            "message" => "API SaaS de asistencia funcionando correctamente",
            "timezone" => "America/Lima",
            "endpoints" => [
                "identify" => "/api/v1/kiosk/identify",
                "employee_status" => "/api/v1/kiosk/employees/{code}/status",
                "mark" => "/api/v1/attendance/mark",
                "today" => "/api/v1/attendance/today/{code}",
                "sync" => "/api/v1/kiosk/sync"
            ],
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
