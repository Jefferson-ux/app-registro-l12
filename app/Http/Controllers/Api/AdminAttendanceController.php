<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAttendanceSessionResource;
use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    // GET /api/v1/admin/attendance (Listado)
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $sessions = AttendanceSession::with('employee') // Solo cargamos employee
            ->where('tenant_id', $tenantId)
            ->latest('attendance_date')
            ->paginate(10);

        return AdminAttendanceSessionResource::collection($sessions)
            ->additional([
                'success' => true,
                'status'  => 200,
            ]);
    }

    // GET /api/v1/admin/attendance/{id} (Detalle)
    public function show(string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $session = AttendanceSession::with('employee')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => new AdminAttendanceSessionResource($session),
        ], 200);
    }

    public function summary(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        // Si no mandan fecha en el query string, asume el día de hoy
        $date = $request->input('date', now()->format('Y-m-d'));

        $stats = AttendanceSession::where('tenant_id', $tenantId)
            ->where('attendance_date', $date)
            ->selectRaw("
            COUNT(*) as total_records,
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
            COALESCE(SUM(late_minutes), 0) as total_late_minutes,
            COALESCE(SUM(overtime_minutes), 0) as total_overtime_minutes
        ")
            ->first();

        return response()->json([
            'success' => true,
            'status'  => 200,
            'data'    => [
                'date'                   => $date,
                'total_records'          => (int) ($stats->total_records ?? 0),
                'present'                => (int) ($stats->present_count ?? 0),
                'late'                   => (int) ($stats->late_count ?? 0),
                'absent'                 => (int) ($stats->absent_count ?? 0),
                'total_late_minutes'     => (int) ($stats->total_late_minutes ?? 0),
                'total_overtime_minutes' => (int) ($stats->total_overtime_minutes ?? 0),
            ],
        ], 200);
    }
}
