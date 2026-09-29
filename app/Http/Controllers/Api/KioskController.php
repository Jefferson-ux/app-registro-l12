<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    // ? - Obtener el Status del Empleado según su propio código ...
    public function getEmployeeStatus(string $code)
    {
        $employee = Employee::where('employee_code', $code)->first();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => new EmployeeStatus($employee),
            'meta' => [
                'checked_at' => now()->toISOString(),
            ],
        ]);
    }


    // ? - Identificar al Empleado Autenticado ...
    public function employeeIdentify(Request $request)
    {

        $validated = $request->validate([
            'employee_code' => 'required|string',
        ]);

        $employee = Employee::where('employee_code', $validated['employee_code'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->first();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'employee_code' => $employee->employee_code,
                'name' => "{$employee->first_name} {$employee->last_name}",
                'photo_url' => $employee->photo_url ?? null,
            ],
        ]);
    }
}
