<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceStatusDetailResource;
use App\Http\Resources\EmployeeAttendanceStatusResource;
use App\Http\Resources\EmployeeResource;
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

        // Ejecutamos el análisis de su horario para el día de hoy
        $scheduleAnalysis = $employee->todayScheduleAnalysis();

        // FILTRO A: No tiene un horario o contrato activo en general hoy
        if (!$scheduleAnalysis['has_active_contract']) {
            return response()->json([
                'success' => 200,
                'message' => 'Estado solicitado Correctamente.',
                'data' => [
                    'status_message' => 'No puedes registrar asistencia porque no cuentas con un contrato o periodo de horario activo en el sistema',
                    'employee' => new EmployeeResource($employee),
                ]
            ], 200);
        }

        // FILTRO B: Tiene contrato, pero HOY no es su día laborable (Ej: Sábado con horas NULL en tu VIEW)
        if (!$scheduleAnalysis['is_working_day_today']) {
            return response()->json([
                'success' => true,
                'status_message' => 'Estado solicitado Correctamente.',
                'data' => [
                    'message' => 'Hoy no es un día laborable asignado en tu jornada de trabajo actual.',
                    'employee' => new EmployeeResource($employee),
                ]
            ], 200);
        }

        $session = $this->todaySessionFor($employee);

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Estado solicitado Correctamente',
            'data' => [
                'employee' => new EmployeeResource($employee),
                'attendance_status' => new AttendanceStatusDetailResource($session)
            ],
            'meta' => [
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }

    // ? - Identificar al Empleado Autenticado ...
    public function identify(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

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

        $session = $this->todaySessionFor($employee);

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Empleado identificado correctamente',
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'code' => $employee->employee_code,
                    'full_name' => "{$employee->first_name} {$employee->last_name}",
                    'photo_url' => $employee->photo_url ?? null,
                ],
                'attendance' => new EmployeeAttendanceStatusResource($session),
            ],
            'meta' => [
                'checked_at' => now()->toIso8601String(),
            ],
        ], 201);
    }

    private function todaySessionFor(Employee $employee): AttendanceSession
    {
        $today = Carbon::today()->toDateString();

        $session = AttendanceSession::with('employee')
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($session) {
            return $session;
        }

        $session = new AttendanceSession([
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'attendance_date' => $today,
        ]);
        $session->setRelation('employee', $employee);

        return $session;
    }
}
