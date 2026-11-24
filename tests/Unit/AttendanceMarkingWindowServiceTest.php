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
    public function test_entry_is_allowed_60_minutes_before_its_schedule(): void
    {
        $this->expectNotToPerformAssertions();
        $service = new AttendanceMarkingWindowService;
        $scheduleDay = new WorkScheduleDay(['check_in_time' => '08:00:00']);

        $service->validateTiming(
            AttendanceType::Entry,
            $scheduleDay,
            null,
            Carbon::parse('2026-11-18 07:00:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_entry_is_rejected_before_60_minutes_window(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Entry,
            new WorkScheduleDay(['check_in_time' => '08:00:00']),
            null,
            Carbon::parse('2026-11-18 06:59:59', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_exit_is_rejected_before_60_minutes_window(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Exit,
            new WorkScheduleDay(['check_out_time' => '17:00:00']),
            null,
            Carbon::parse('2026-11-18 15:59:59', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_exit_is_allowed_60_minutes_before_its_schedule(): void
    {
        $this->expectNotToPerformAssertions();
        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Exit,
            new WorkScheduleDay(['check_out_time' => '17:00:00']),
            null,
            Carbon::parse('2026-11-18 16:00:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_break_start_is_allowed_from_30_minutes_before_until_the_marking_cutoff(): void
    {
        $service = new AttendanceMarkingWindowService;
        $scheduleDay = new WorkScheduleDay([
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        $service->validateTiming(
            AttendanceType::BreakStart,
            $scheduleDay,
            null,
            Carbon::parse('2026-11-18 11:30:00', 'America/Lima'),
            'America/Lima',
        );

        $service->validateTiming(
            AttendanceType::BreakStart,
            $scheduleDay,
            null,
            Carbon::parse('2026-11-18 22:59:00', 'America/Lima'),
            'America/Lima',
        );

        $this->expectException(AttendanceException::class);
        $service->validateTiming(
            AttendanceType::BreakStart,
            $scheduleDay,
            null,
            Carbon::parse('2026-11-18 11:29:59', 'America/Lima'),
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

    public function test_break_end_is_allowed_after_its_scheduled_end_time(): void
    {
        $this->expectNotToPerformAssertions();
        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::BreakEnd,
            new WorkScheduleDay(['break_end_time' => '13:00:00']),
            new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']),
            Carbon::parse('2026-11-18 13:10:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_marking_is_rejected_after_11_pm(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Entry,
            new WorkScheduleDay(['check_in_time' => '08:00:00']),
            null,
            Carbon::parse('2026-11-18 23:00:01', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_exit_must_wait_10_minutes_after_break_end(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Exit,
            new WorkScheduleDay(['check_out_time' => '17:00:00']),
            new AttendanceSession(['break_end_at' => '2026-11-18 16:00:00']),
            Carbon::parse('2026-11-18 16:09:59', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_exit_is_allowed_10_minutes_after_break_end(): void
    {
        $this->expectNotToPerformAssertions();

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Exit,
            new WorkScheduleDay(['check_out_time' => '17:00:00']),
            new AttendanceSession(['break_end_at' => '2026-11-18 16:00:00']),
            Carbon::parse('2026-11-18 16:10:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_break_start_must_wait_10_minutes_after_check_in(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::BreakStart,
            new WorkScheduleDay(['break_start_time' => '12:00:00']),
            new AttendanceSession(['check_in_at' => '2026-11-18 11:30:00']),
            Carbon::parse('2026-11-18 11:39:59', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_break_start_is_allowed_10_minutes_after_check_in(): void
    {
        $this->expectNotToPerformAssertions();

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::BreakStart,
            new WorkScheduleDay(['break_start_time' => '12:00:00']),
            new AttendanceSession(['check_in_at' => '2026-11-18 11:30:00']),
            Carbon::parse('2026-11-18 11:40:00', 'America/Lima'),
            'America/Lima',
        );
    }

    public function test_exit_must_wait_10_minutes_after_check_in_when_no_break_is_scheduled(): void
    {
        $this->expectException(AttendanceException::class);

        (new AttendanceMarkingWindowService)->validateTiming(
            AttendanceType::Exit,
            new WorkScheduleDay(['check_out_time' => '17:00:00']),
            new AttendanceSession(['check_in_at' => '2026-11-18 16:00:00']),
            Carbon::parse('2026-11-18 16:09:59', 'America/Lima'),
            'America/Lima',
        );
    }
}
