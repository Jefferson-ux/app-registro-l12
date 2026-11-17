<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('messages.attendance_widget.heading') }}
        </x-slot>

        <style>
            .attendance-history-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
            }

            .attendance-history-table th {
                padding: 10px 12px;
                font-size: 13px;
                font-weight: 800;
                color: #707c83;
                border-bottom: 3px solid #a3aab8;
            }

            .attendance-history-table td {
                padding: 10px 12px;
                font-size: 14px;
                color: #4f5763;
                border-bottom: 1px solid #a0a9bb;
            }

            .status-present {
                color: #16a34a;
                font-weight: 600;
            }

            .status-late {
                color: #eab308;
                font-weight: 600;
            }

            .status-absent {
                color: #ef4444;
                font-weight: 600;
            }

            .status-break {
                color: #a855f7;
                font-weight: 600;
            }

            .status-out {
                color: #3b82f6;
                font-weight: 600;
            }

            .status-other {
                color: #9ca3af;
                font-weight: 600;
            }

            .badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
            }

            .status-present {
                color: #16a34a;
                background: rgba(22, 163, 74, 0.15);
            }

            .status-late {
                color: #eab308;
                background: rgba(234, 179, 8, 0.15);
            }

            .status-absent {
                color: #ef4444;
                background: rgba(239, 68, 68, 0.15);
            }

            .status-break {
                color: #a855f7;
                background: rgba(168, 85, 247, 0.15);
            }

            .status-out {
                color: #3b82f6;
                background: rgba(59, 130, 246, 0.15);
            }

            .status-other {
                color: #9ca3af;
                background: rgba(156, 163, 175, 0.15);
            }
        </style>

        <div style="max-width: 320px; margin-bottom: 14px;">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('messages.attendance_widget.search') }}" />
            </x-filament::input.wrapper>
        </div>

        <div style="overflow-x: auto; max-height: 400px; overflow-y: auto;">
            <table class="attendance-history-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.attendance_widget.employee') }}</th>
                        <th>{{ __('messages.attendance_widget.mark') }}</th>
                        <th>{{ __('messages.attendance_widget.time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $e)
                    @php
                    [$text, $class] = match ($e['column']) {
                    'check_in_at' => match ($e['status']) {
                    'present' => [__('messages.attendance_marks.on_time'), 'status-present'],
                    'late' => [__('messages.attendance_marks.late'), 'status-late'],
                    'absent' => [__('messages.attendance_marks.absent'), 'status-absent'],
                    default => [$e['status'] ?? '-', 'status-other'],
                    },
                    'break_start_at' => [__('messages.attendance_marks.break_start'), 'status-break'],
                    'break_end_at' => [__('messages.attendance_marks.break_end'), 'status-break'],
                    'check_out_at' => [__('messages.attendance_marks.check_out'), 'status-out'],
                    default => ['-', 'status-other'],
                    };
                    @endphp
                    <tr wire:key="event-{{ $loop->index }}">
                        <td>
                            {{ $e['employee']?->first_name ?? 'N/A' }}
                            {{ $e['employee']?->last_name ?? '' }}
                        </td>
                        <td>
                            <span class="badge {{ $class }}">{{ $text }}</span>
                        </td>
                        <td>
                            {{ self::formatHumanTime($e['at']) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #9ca3af; padding: 20px;">
                            {{ __('messages.attendance_widget.empty') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>