<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAttendanceIncidentRequest;
use App\Http\Requests\Api\MarkRequest;
use App\Http\Resources\AttendanceTodayResource;
use App\Http\Resources\EmployeeResource;
use App\Models\AttendanceSession;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function attendanceToday(string $code)
    {

        $tenantId = auth()->user()->tenant_id;

        $today = Carbon::today()->format('Y-m-d');

        $employee = Employee::where('tenant_id', $tenantId)
            ->where('employee_code', $code)
            ->firstOrFail();

        $session = AttendanceSession::with('employee')
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        if (! $session) {
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'El empleado no registra sesión de asistencia para el día de hoy.',
                'employee' => new EmployeeResource($employee),
                'attendance_date' => $today,
                'session' => null,
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => new AttendanceTodayResource($session),
            'meta' => [
                'checked_at' => now()->toISOString(),
            ],
        ], 200);
    }


    public function storeIncident(StoreAttendanceIncidentRequest $request, string $id)
    {
        $validated = $request->validated();

        $tenantId = auth()->user()->tenant_id;

        $session = AttendanceSession::where('tenant_id', $tenantId)
            ->findOrFail($id);

        $incident = $session->incidents()->create([
            'tenant_id'     => $session->tenant_id,
            'employee_id'   => $session->employee_id,
            'incident_type' => $validated['incident_type'],
            'incident_date' => $session->attendance_date,
            'description'   => $validated['description'] ?? null,
            'status'        => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'status'  => 201,
            'message' => 'Incidencia registrada correctamente.',
            'data'    => $incident,
        ], 201);
    }




// ! =============================================================
// ! ===================== Marcaciones ===========================
// ! =============================================================

public function mark(MarkRequest $request){
    $validated = $request->validated();
    $tenantId = auth()->user()->tenant_id;

    // 1. Buscar al empleado por su código en el tenant actual
    $employee = Employee::where('tenant_id', $tenantId)
        ->where('employee_code', $validated['employee_code'])
        ->first();

    if (!$employee) {
        return response()->json([
            'success' => false,
            'message' => 'El código de empleado no existe en este tenant.'
        ], 404);
    }

    $today = now()->toDateString(); // YYYY-MM-DD
    $now = now();
    $type = $validated['type'];

    // 2. Buscar la sesión de hoy o preparar una nueva instancia
    $session = AttendanceSession::firstOrNew([
        'tenant_id'       => $tenantId,
        'employee_id'     => $employee->id,
        'attendance_date' => $today,
    ]);

    // 3. Evaluar el tipo de marcación
    switch ($type) {
        case 'ENTRY':
            if (!$session->exists) {
                $session->check_in_at = $now;
                $session->scheduled_minutes = 1; // Ejemplo: 8 horas
                $session->late_minutes = 5;
                $session->status = $session->late_minutes > 0 ? 'late' : 'present';
                $session->save();
            } else {
                $session->update([
                    'check_in_at' => $now,
                ]);
            }
            
            $message = 'Marcación de ENTRADA registrada correctamente.';
            break;

        case 'EXIT':
            if (!$session->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existe una marcación de entrada previa para el día de hoy.'
                ], 422);
            } 

            $checkInTime = Carbon::parse($session->check_in_at);
            $workedMinutes = $checkInTime->diffInMinutes($now);

            $exit_status = $workedMinutes < $session->scheduled_minutes ? 'incomplete' : $session->status;

            $session->update([
                'check_out_at'   => $now,
                'worked_minutes' => $workedMinutes,
                'status' => $exit_status
            ]);

            $message = 'Marcación de SALIDA registrada correctamente.';
            break;

        case 'BREAK_START':
            if (!$session->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe registrar su entrada antes de marcar el inicio de un descanso.'
                ], 422);
            }

            // Aquí guardas el momento en que salió a descanso)
            $session->update([
                'break_start_at' => $now,
            ]);

            $message = 'Inicio de descanso registrado.';
            break;

        case 'BREAK_END':
            if (!$session->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existe una sesión activa para registrar el fin de descanso.'
                ], 422);
            }

            // Opcional: Validar que realmente haya salido a descanso antes
            if (!$session->break_start_at) {                 return response()->json([
                    'success' => false,
                    'message' => 'El empleado no ha salido a su Break respectivo.'
                ], 422); }
            
            // Guardas el fin del descanso
            $session->update([
                'break_end_at' => $now,
            ]);

            $message = 'Fin de descanso registrado correctamente.';
            break;

        default:
            return response()->json(['success' => false, 'message' => 'Tipo de marcación no válido.'], 400);
    }

    return response()->json([
        'attendance'=>$session->id,
        'success' => true,
        'status'  => 200,
        'message' => $message,       
        'data'    => [
            'employee'=> [new EmployeeResource($employee)],
            'attendance' => $session->fresh()]
    ], 200);
}

}
