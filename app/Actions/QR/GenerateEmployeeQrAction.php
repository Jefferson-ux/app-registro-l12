<?php

namespace App\Actions\QR;

use App\Models\Employee;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class GenerateEmployeeQrAction
{
    public function execute(Employee $employee): string
    {
        $result = (new Builder(
            writer: new PngWriter(),
            data: $employee->employee_code,
            size: 400,
            margin: 10,
        ))->build();

        $path = "qr/{$employee->tenant_id}/{$employee->employee_code}.png";

        // Si el código cambió, borra el QR viejo
        if ($employee->qr_image_path && $employee->qr_image_path !== $path) {
            Storage::disk('local')->delete($employee->qr_image_path);
        }

        Storage::disk('local')->put($path, $result->getString());

        $employee->qr_image_path = $path;
        $employee->saveQuietly();

        return $path;
    }
}