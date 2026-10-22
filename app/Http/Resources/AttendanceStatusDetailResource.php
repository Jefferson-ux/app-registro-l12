<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [
                'employee_code'  => null,
                'work_date'      => now()->toDateString(),
                'current_status' => 'NOT_STARTED',
                'next_action'    => 'ENTRY',
                'last_mark'      => null,
            ];
        }

        $currentStatus = $this->determineCurrentStatus();
        $nextAction = $this->determineNextAction($currentStatus);

        return [
            // Busca el código tanto en la relación como en la columna directa por si acaso
            'employee_code'  => $this->employee_code ?? $this->employee?->code ?? null,
            
            // Soporta si tu columna se llama work_date o simplemente date
            'work_date'      => $this->work_date ?? $this->date ?? now()->toDateString(),
            
            'current_status' => $currentStatus,
            'next_action'    => $nextAction,
            'last_mark'      => $this->resolveLastMark(),
        ];
    }
}