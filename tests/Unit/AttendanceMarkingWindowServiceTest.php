<?php

namespace Tests\Unit;

use App\Enums\AttendanceType;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\WorkScheduleDay;
use App\Services\AttendanceMarkingWindowService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceMarkingWindowServiceTest extends TestCase
{
    public function test_entry_is_allowed_at_the_earliest_time_in_its_window(): void
    {
        $this->expectNotToPerformAssertions();
        $service = new AttendanceMarkingWindowService;
        $scheduleDay = new WorkScheduleDay(['check_in_time' => '08:00:00']);

        $service->validateTiming(
            AttendanceType::Entry,
            $scheduleDay,
            null,
            Carbon::parse('2026-11-18 07:15:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_entry_is_rejected_before_its_early_window(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Entry,
            new WorkScheduleDay(['check_in_time' => '08:00:00']),
            null,
            Carbon::parse('2026-11-18 07:14:59', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_exit_is_rejected_before_its_early_window(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Exit,
            new WorkScheduleDay(['check_out_time' => '17:00:00']),
            null,
            Carbon::parse('2026-11-18 16:14:59', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_exit_is_allowed_at_the_earliest_time_in_its_window(): void
    {
        $this->expectNotToPerformAssertions();
        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Exit,
            new WorkScheduleDay(['check_out_time' => '17:00:00']),
            null,
            Carbon::parse('2026-11-18 16:15:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_break_start_is_limited_to_its_early_window_and_minimum_duration(): void
    {
        $service = new AttendanceMarkingWindowService;
        $scheduleDay = new WorkScheduleDay([
            'break_start_time' => '10:00:00',
            'break_end_time' => '10:30:00',
        ]);

        $service->validateTiming(
            AttendanceType::BreakStart,
            $scheduleDay,
            null,
            Carbon::parse('2026-11-18 09:30:00', 'America/Lima'),
            'America/Lima',
        );

        $this->expectException(AttendanceException::class);
        $service->validateTiming(
            AttendanceType::BreakStart,
            $scheduleDay,
            null,
            Carbon::parse('2026-11-18 10:21:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_break_end_requires_the_configured_minimum_break_duration(): void
    {
        $service = new AttendanceMarkingWindowService;
        $session = new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']);

        $this->expectException(AttendanceException::class);
        $service->validateTiming(
            AttendanceType::BreakEnd,
            new WorkScheduleDay,
            $session,
            Carbon::parse('2026-11-18 12:09:59', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_break_end_is_allowed_after_the_minimum_break_duration(): void
    {
        $this->expectNotToPerformAssertions();
        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::BreakEnd,
            new WorkScheduleDay,
            new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']),
            Carbon::parse('2026-11-18 12:10:00', 'America/Lima'),
            'America/Lima',
        );
    }
}
