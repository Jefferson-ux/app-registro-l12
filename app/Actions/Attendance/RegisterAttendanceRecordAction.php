<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RegisterAttendanceRecordAction
{
    public function execute(
        Employee $employee,
        AttendanceType $type,
        Carbon $recordedAt,
        Request $request,
        string $method = 'kiosk',
    ): AttendanceRecord {
        return AttendanceRecord::create([
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'type' => $type->toRecordType(),
            'recorded_at' => $recordedAt,
            'method' => $method,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'ip_address' => $request->ip(),
            'device_identifier' => $request->input('device_identifier'),
            'notes' => $request->input('notes'),
        ]);
    }
}
