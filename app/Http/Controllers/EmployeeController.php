<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{

    public function show(string $id)
    {
        $employee = Employee::find($id);

        return response()->json($employee, 200);
    }

    public function showAttendance(string $employee)
    {
        $attendance = AttendanceSession::where('employee_id', $employee)->get();
        $employee_data = Employee::find($employee);

        return response()->json([
            "employee" => $employee_data->first_name,
            "data" => $attendance,
        ], 200);
    }
}
