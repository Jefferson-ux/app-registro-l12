<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeAttendanceStatusResource extends JsonResource
{

    public function toArray(Request $request): array
    {
// Si no hay sesión registrada hoy ($this->resource es null)
        if (!$this->resource) {
            return [
                'current_status' => 'NOT_STARTED',
                'next_action' => 'ENTRY',
                'last_mark_at' => null,
            ];
        }

        // Determinamos el estado dinámico basado en las marcas registradas
        $currentStatus = $this->determineCurrentStatus();
        $nextAction = $this->determineNextAction($currentStatus);

        // Recopilamos las marcas que no estén vacías para hallar la más reciente
        $marks = array_filter([
            $this->check_in_at,
            $this->break_start_at,
            $this->break_end_at,
            $this->check_out_at,
        ]);

        $lastMarkAt = !empty($marks) ? max($marks) : null;

        return [
            'current_status' => $currentStatus,
            'next_action'    => $nextAction,
            'last_mark_at'   => $lastMarkAt ? Carbon::parse($lastMarkAt)->toIso8601String() : null,
        ];
    }

    private function determineNextAction(?string $status): string
    {
        return match ($status) {
            'NOT_STARTED' => 'ENTRY',
            'WORKING' => 'BREAK_START', // O 'EXIT' según la regla de negocio
            'ON_BREAK' => 'BREAK_END',
            'COMPLETED' => 'EXIT',
            default => 'ENTRY',
        };
    }
    private function determineCurrentStatus(): string
    {
        if ($this->check_out_at) {
            // Si ya marcó salida, verificamos si completó las horas programadas
            $worked = $this->worked_minutes ?? 0;
            $scheduled = $this->scheduled_minutes ?? 0;

            // Si trabajó menos de lo programado (y hay un horario establecido)
            if ($scheduled > 0 && $worked < $scheduled) {
                return 'INCOMPLETE';
            } else {
                return 'COMPLETED';
            }
        }
        
        if ($this->break_end_at) {
            return 'WORKING'; // Regresó del break y está laborando nuevamente
        }
        
        if ($this->break_start_at) {
            return 'ON_BREAK'; // Inició su descanso o break
        }
        
        if ($this->check_in_at) {
            return 'WORKING'; // Marcó entrada pero aún no sale a break
        }

        return 'NOT_STARTED';
    }
        
}
