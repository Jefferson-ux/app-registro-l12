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

    private function resolveLastMark(): ?array
    {
        $marks = [
            'ENTRY'       => $this->check_in_at,
            'BREAK_START' => $this->break_start_at,
            'BREAK_END'   => $this->break_end_at,
            'EXIT'        => $this->check_out_at,
        ];

        // Filtramos las marcas que tengan algún valor válido
        $validMarks = array_filter($marks, fn($value) => !empty($value));

        if (empty($validMarks)) {
            return null;
        }

        $latestType = null;
        $latestTime = null;

        foreach ($validMarks as $type => $time) {
            $carbonTime = Carbon::parse($time);
            if (!$latestTime || $carbonTime->gt($latestTime)) {
                $latestTime = $carbonTime;
                $latestType = $type; // Aquí se asigna explícitamente 'ENTRY', 'BREAK_START', etc.
            }
        }

        return [
            'type'        => $latestType,
            'recorded_at' => $latestTime->toIso8601String(),
        ];
    }

    private function determineCurrentStatus(): string
    {
        if ($this->check_out_at) {
            $worked = $this->worked_minutes ?? 0;
            $scheduled = $this->scheduled_minutes ?? 0;
            
            if ($scheduled > 0 && $worked < $scheduled) {
                return 'INCOMPLETE';
            }
            return 'COMPLETED';
        }
        
        if ($this->break_end_at) return 'WORKING';
        if ($this->break_start_at) return 'ON_BREAK';
        if ($this->check_in_at) return 'WORKING';

        return 'NOT_STARTED';
    }

    private function determineNextAction(string $status): string
    {
        return match ($status) {
            'NOT_STARTED' => 'ENTRY',
            'WORKING'     => 'BREAK_START',
            'ON_BREAK'    => 'BREAK_END',
            'COMPLETED', 
            'INCOMPLETE'  => 'COMPLETED',
            default       => 'ENTRY',
        };
    }
}