<?php

namespace App\Enums;

enum AttendanceType: string
{
    case Entry = 'ENTRY';
    case Exit = 'EXIT';
    case BreakStart = 'BREAK_START';
    case BreakEnd = 'BREAK_END';

    public function toRecordType(): string
    {
        return match ($this) {
            self::Entry      => 'check_in',
            self::Exit       => 'check_out',
            self::BreakStart => 'break_start',
            self::BreakEnd   => 'break_end',
        };
    }
}
