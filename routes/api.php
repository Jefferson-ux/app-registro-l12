<?php

use App\Http\Controllers\Api\AdminAttendanceController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KioskController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    //! API de entrada
    // api ==> GET https::/api/v1/
    Route::get('/', [HealthController::class, 'index'])->name('health-check');

    //! APIs de Prueba
    // api ==> GET http::/api/v1/employees/{employee}
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('get-employee');
    // api ==> GET http::/api/v1/employees/{employee}
    Route::get('/attendance/{employee}', [EmployeeController::class, 'showAttendance'])->name('show-attendance');

    //! APIs Autenticadas
    // api ==> POST http::/api/v1/login
    Route::post('/login', [AuthController::class, 'login'])->name('api.login'); // Login de la API

    Route::middleware('auth:sanctum')->group(function () {
        // api ==> POST http::/api/v1/logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

        //! APIs de Negocio
        // api ==> GET http::/api/v1/kiosk/employees/{code}/status
        Route::get('/kiosk/employees/{code}/status', [KioskController::class, 'getEmployeeStatus'])->name('get-employee-status');

        // api ==> POST http::/api/v1/kiosk/identify
        Route::post('/kiosk/identify', [KioskController::class, 'identify'])->name('employee-identify');

        //! APIs de Asistencia
        // api ==> GET http::/api/v1/attendance/mark
        Route::post('/attendance/mark', [AttendanceController::class, 'mark'])->name('attendance-mark');

        // api ==> GET http::/api/v1/attendance/today/{code}
        Route::get('/attendance/today/{code}', [AttendanceController::class, 'attendanceToday'])->name('get-attendance-today');

        // api ==> POST http::/api/v1/attendance/{id}/incidents
        Route::post('/attendance/{id}/incidents', [AttendanceController::class, 'storeIncident'])->name('store-attendance-incident');

        //! Admin Attendance APIs
        // api ==> GET http::/api/v1/admin/attendance/summary
        Route::get('/admin/attendance/summary', [AdminAttendanceController::class, 'summary'])->name('get-admin-attendance-summary');

        // api ==> GET http::/api/v1/admin/attendance
        Route::get('/admin/attendance', [AdminAttendanceController::class, 'index'])->name('get-admin-attendance');

        // api ==> GET http::/api/v1/admin/attendance/{id}
        Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('get-admin-attendance-by-id');

        //! Admin Kiosk APIs
    });
});
