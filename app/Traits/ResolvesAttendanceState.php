<?php

namespace App\Traits;

use Carbon\Carbon;

trait ResolvesAttendanceState
{
    /**
     * Devuelve la última marca registrada: ['type' => ..., 'time' => Carbon] o null.
     */
    protected function latestMark(): ?array
    {
        $marks = [
            'ENTRY'       => $this->check_in_at,
            'BREAK_START' => $this->break_start_at,
            'BREAK_END'   => $this->break_end_at,
            'EXIT'        => $this->check_out_at,
        ];

        $latestType = null;
        $latestTime = null;

        foreach (array_filter($marks) as $type => $time) {
            $carbonTime = Carbon::parse($time);

            if (!$latestTime || $carbonTime->gt($latestTime)) {
                $latestTime = $carbonTime;
                $latestType = $type;
            }
        }

        return $latestType ? ['type' => $latestType, 'time' => $latestTime] : null;
    }

    protected function determineCurrentStatus(): string
    {
        if ($this->check_out_at) {
            $worked    = $this->worked_minutes ?? 0;
            $scheduled = $this->scheduled_minutes ?? 0;

            return ($scheduled > 0 && $worked < $scheduled) ? 'INCOMPLETE' : 'COMPLETED';
        }

        if ($this->break_end_at)   return 'WORKING';
        if ($this->break_start_at) return 'ON_BREAK';
        if ($this->check_in_at)    return 'WORKING';

        return 'NOT_STARTED';
    }

    protected function determineNextAction(string $status): string
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