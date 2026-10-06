<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceTodayResource;
use App\Http\Resources\EmployeeAttendanceStatusResource;
use App\Http\Resources\AttendanceStatusDetailResource;
use App\Models\AttendanceSession;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    // ? - Obtener el Status del Empleado según su propio código ...
    public function getEmployeeStatus(string $code)
    {
        $tenantId = auth()->user()->tenant_id;
        $today = Carbon::today()->format('Y-m-d');
        
        $employee = Employee::where('tenant_id', $tenantId)
            ->where('employee_code', $code)
            ->first();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Empleado no encontrado en esta empresa',
                ], 404);
            }

        $session = AttendanceSession::with('employee')
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$session) {
            // Creamos una sesión "fantasma" solo para que el resource tenga el empleado y devuelva NOT_STARTED
            $session = new AttendanceSession([
                'employee_id' => $employee->id,
                'attendance_date' => $today,
            ]);
            $session->setRelation('employee', $employee);
        } else {
            $session->loadMissing('employee');
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => "Estado solicitado Correctamente",
            'data' => new AttendanceStatusDetailResource($session),
            'meta' => [
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }
                        
                        
    // ? - Identificar al Empleado Autenticado ...
    public function identify(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $today = Carbon::today()->format('Y-m-d');
                            
        $validated = $request->validate([
            'employee_code' => 'required|string',
        ]);

        $employee = Employee::where('employee_code', $validated['employee_code'])
            ->where('tenant_id', $tenantId)
            ->first();
    
        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        $session = AttendanceSession::with('employee')
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();



        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => "Empleado identificado correctamente",
            'data' => [
                "employee"=> [
                    'id'=>$employee->id,
                    'code' => $employee->employee_code,
                    'full_name' => "{$employee->first_name} {$employee->last_name}",
                    'photo_url' => $employee->photo_url ?? null
                    ],
                "attendance"=>new EmployeeAttendanceStatusResource($session)
                ],
            'meta' => [
                'checked_at' => now()->toIso8601String(),
            ],
        ], 201);
    }
}
