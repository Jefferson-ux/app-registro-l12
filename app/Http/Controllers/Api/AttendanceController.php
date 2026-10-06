<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAttendanceIncidentRequest;
use App\Http\Requests\Api\MarkRequest;
use App\Http\Resources\AttendanceTodayResource;
use App\Http\Resources\EmployeeResource;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
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
                'checked_at' => now()->toIso8601String(),
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

    // 2. Buscar la sesión de hoy o preparar una nueva instancia
    $session = AttendanceSession::firstOrNew([
        'tenant_id'       => $tenantId,
        'employee_id'     => $employee->id,
        'attendance_date' => $today,
    ]);

    $type = $validated['type'];
    // 3. Evaluar el tipo de marcación
    switch ($type) {
case 'ENTRY':
            // Evitar duplicado de Entrada si ya existe un check_in_at registrado
            if ($session->exists && $session->check_in_at) {
                return response()->json([
                    'status' => 409,
                    'success' => false,
                    'message' => 'Ya registraste tu marcación de ENTRADA para el día de hoy.',
                ], 409);
            }

            // 1. Obtener el número del día de la semana actual (1 = Lunes ... 7 = Domingo)
            $dayOfWeek = $now->dayOfWeekIso;

            $scheduleDay = EmployeeSchedule::where('employee_id', $employee->id)
                ->where('status', true)
                ->where('start_date', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', $today);
                })
                ->with(['workSchedule.days' => function ($query) use ($dayOfWeek) {
                    $query->where('day_of_week', $dayOfWeek);
                }])
                ->first()?->workSchedule?->days->first();

            $lateMinutes = 0;
            $status = 'present';
            $scheduledMinutes = 0;

            if ($scheduleDay && $scheduleDay->is_working_day) {
                // Combinar la fecha de hoy con la hora de entrada y salida programada
                $checkInTimeString = date('H:i:s', strtotime($scheduleDay->check_in_time));
                $checkOutTimeString = date('H:i:s', strtotime($scheduleDay->check_out_time));

                $scheduledCheckIn = Carbon::parse($today . ' ' . $checkInTimeString);
                $scheduledCheckOut = Carbon::parse($today . ' ' . $checkOutTimeString);

                // Minutos brutos entre entrada y salida
                $scheduledMinutes = $scheduledCheckIn->diffInMinutes($scheduledCheckOut);

                // ==========================================
                // RESTAR EL TIEMPO DE BREAK PROGRAMADO
                // ==========================================
                $breakDuration = $scheduleDay->break_duration_minutes ?? 0; 
                
                if (!empty($scheduleDay->break_start_time) && !empty($scheduleDay->break_end_time)) {
                    $breakStartString = date('H:i:s', strtotime($scheduleDay->break_start_time));
                    $breakEndString = date('H:i:s', strtotime($scheduleDay->break_end_time));

                    $schBreakStart = Carbon::parse($today . ' ' . $breakStartString);
                    $schBreakEnd = Carbon::parse($today . ' ' . $breakEndString);
                    
                    $breakDuration = $schBreakStart->diffInMinutes($schBreakEnd);
                }

                // Descontamos el break del total de minutos laborables netos
                $scheduledMinutes = max(0, $scheduledMinutes - $breakDuration);

                $tolerance = $scheduleDay->check_in_tolerance_minutes ?? 0;

                // Si llegó después de la hora programada + minutos de tolerancia
                if ($now->gt($scheduledCheckIn->copy()->addMinutes($tolerance))) {
                    // Calculamos la tardanza exacta en minutos desde la hora programada
                    $lateMinutes = $scheduledCheckIn->diffInMinutes($now);
                    $status = 'late';
                }
            }

            if (!$session->exists) {
                $session->check_in_at = $now;
                $session->scheduled_minutes = $scheduledMinutes;
                $session->late_minutes = $lateMinutes;
                $session->status = $status;
                $session->save();
            } else {
                $session->update([
                    'check_in_at' => $now,
                    'scheduled_minutes' => $scheduledMinutes,
                    'late_minutes' => $lateMinutes,
                    'status' => $status,
                ]);
            }
            
            $message = 'Marcación de ENTRADA registrada correctamente.';
            break;
        
        case 'EXIT':
            if (!$session->exists) {
                return response()->json([
                    'status' => 422,
                    'success' => false,
                    'message' => 'No existe una marcación de entrada previa para el día de hoy.'
                ], 422);
            }
            // Evitar duplicado de Salida si ya fue registrada
            if ($session->check_out_at) {
                return response()->json([
                    'status' => 409,
                    'success' => false,
                    'message' => 'Ya registraste tu marcación de SALIDA para el día de hoy.'
                ], 409);
            }

            // 1. Obtener el horario programado del día para comparar con la salida
            $dayOfWeek = $now->dayOfWeekIso;
            $scheduleDay = \App\Models\EmployeeSchedule::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->where('start_date', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', $today);
                })
                ->with(['workSchedule.days' => function ($query) use ($dayOfWeek) {
                    $query->where('day_of_week', $dayOfWeek);
                }])
                ->first()?->workSchedule?->days->first();

            $checkInTime = Carbon::parse($session->check_in_at);
            
            // 2. Calcular los minutos brutos desde la entrada hasta ahora
            $workedMinutes = $checkInTime->diffInMinutes($now);

            // 3. Descontar el tiempo de break si se registró
            if ($session->break_start_at && $session->break_end_at) {
                $breakStart = Carbon::parse($session->break_start_at);
                $breakEnd = Carbon::parse($session->break_end_at);
                $breakMinutes = $breakStart->diffInMinutes($breakEnd);
                
                // Restamos el break del tiempo total transcurrido
                $workedMinutes = max(0, $workedMinutes - $breakMinutes);
            }

            $earlyLeaveMinutes = 0;
            $overtimeMinutes = 0;
            $exitStatus = $session->status; // Mantiene 'present' o 'late' previo

            if ($scheduleDay && $scheduleDay->is_working_day) {
                $scheduledCheckOut = Carbon::parse($today . ' ' . $scheduleDay->check_out_time);
                $toleranceOut = $scheduleDay->check_out_tolerance_minutes ?? 0;

                // Evaluación de Salida Temprana (Early Leave)
                // Si sale antes de la hora programada menos su tolerancia
                $limitEarlyLeave = $scheduledCheckOut->copy()->subMinutes($toleranceOut);
                if ($now->lt($limitEarlyLeave)) {
                    $earlyLeaveMinutes = $now->diffInMinutes($scheduledCheckOut);
                    $exitStatus = 'incomplete';
                } 
                // Evaluación de Horas Extra (Overtime)
                // Si sale después de la hora programada de salida
                elseif ($now->gt($scheduledCheckOut)) {
                    $overtimeMinutes = $scheduledCheckOut->diffInMinutes($now);
                }
            }

            // Validar si laboró menos de lo programado globalmente
            if ($workedMinutes < $session->scheduled_minutes && $earlyLeaveMinutes == 0) {
                $exitStatus = 'incomplete';
            }

            $session->update([
                'check_out_at'        => $now,
                'worked_minutes'      => $workedMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'overtime_minutes'    => $overtimeMinutes,
                'status'              => $exitStatus
            ]);

            $message = 'Marcación de SALIDA registrada correctamente.';
            break;

        case 'BREAK_START':
            if (!$session->exists) {
                return response()->json([
                    'status' => 422,
                    'success' => false,
                    'message' => 'Debe registrar su entrada antes de marcar el inicio de un descanso.'
                ], 422);
            }

            // Evitar duplicado de inicio de descanso
            if ($session->break_start_at) {
                return response()->json([
                    'status' => 409,
                    'success' => false,
                    'message' => 'Ya registraste el inicio de tu descanso anteriormente.'
                ], 409);
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
                    'status' => 422,
                    'success' => false,
                    'message' => 'No existe una sesión activa para registrar el fin de descanso.'
                ], 422);
            }

            // Opcional: Validar que realmente haya salido a descanso antes
            if (!$session->break_start_at) {                 
                return response()->json([
                    'status' => 422,
                    'success' => false,
                    'message' => 'El empleado no ha salido a su Break respectivo.'
                ], 422); }
            
            // Evitar duplicado de fin de descanso si ya fue registrado
            if ($session->break_end_at) {
                return response()->json([
                    'status' => 409,
                    'success' => false,
                    'message' => 'Ya registraste el fin de tu descanso anteriormente.'
                ], 409);
            }
            
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
            'employee'=> new EmployeeResource($employee),
            'attendance' => new AttendanceTodayResource($session->fresh())]
    ], 200);
}

}
