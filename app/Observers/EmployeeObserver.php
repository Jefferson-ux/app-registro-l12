<?php

namespace App\Observers;

use App\Actions\Qr\GenerateEmployeeQrAction;
use App\Models\Employee;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        if (empty($employee->employee_code)) {
            return;
        }

        app(GenerateEmployeeQrAction::class)->execute($employee);
    }

    public function updated(Employee $employee): void
    {
        if ($employee->wasChanged('employee_code') || ! $employee->qr_image_path) {
            app(GenerateEmployeeQrAction::class)->execute($employee);
        }
    }
}