<?php

namespace App\Http\Controllers\Api;

use App\Actions\Attendance\MarkAttendanceAction;
use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAttendanceIncidentRequest;
use App\Http\Requests\Api\MarkRequest;
use App\Http\Resources\AttendanceTodayResource;
use App\Http\Resources\MarkResource;
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

    // ! ===================== Marcaciones ===========================
    public function mark(MarkRequest $request, MarkAttendanceAction $markAttendance)
    {
        try {
            $result = $markAttendance->execute($request);
        } catch (AttendanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->status);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'employee' => new EmployeeResource($result['employee']),
                'attendance' => new AttendanceTodayResource($result['session']),
            ],
        ]);
    }
}
