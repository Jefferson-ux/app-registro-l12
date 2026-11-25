@php
$rows = $rows ?? [];
@endphp

<style>
    .emp-fixed-schedule-wrap {
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid rgba(156, 163, 175, 0.35);
        background-color: #ffffff;
    }

    .dark .emp-fixed-schedule-wrap {
        border-color: rgba(255, 255, 255, 0.1);
        background-color: rgb(17, 24, 39);
    }

    .emp-fixed-schedule-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.875rem;
    }

    .emp-fixed-schedule-table thead {
        background-color: rgba(243, 244, 246, 0.85);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgb(75, 85, 99);
    }

    .dark .emp-fixed-schedule-table thead {
        background-color: rgba(255, 255, 255, 0.05);
        color: rgb(209, 213, 219);
    }

    .emp-fixed-schedule-table th,
    .emp-fixed-schedule-table td {
        padding: 0.65rem 1rem;
        white-space: nowrap;
        border-bottom: 1px solid rgba(156, 163, 175, 0.2);
    }

    .dark .emp-fixed-schedule-table th,
    .dark .emp-fixed-schedule-table td {
        border-bottom-color: rgba(255, 255, 255, 0.08);
    }

    .emp-fixed-schedule-table tbody tr:last-child td {
        border-bottom: none;
    }

    .emp-fixed-row-active {
        color: rgb(17, 24, 39);
        transition: background-color 0.15s ease;
    }

    .dark .emp-fixed-row-active {
        color: rgb(243, 244, 246);
    }

    .emp-fixed-row-active:hover {
        background-color: rgba(59, 130, 246, 0.05);
    }

    .dark .emp-fixed-row-active:hover {
        background-color: rgba(255, 255, 255, 0.03);
    }

    .emp-fixed-row-disabled {
        background-color: rgba(243, 244, 246, 0.75);
        color: rgb(156, 163, 175);
        opacity: 0.55;
        cursor: not-allowed;
        user-select: none;
    }

    .dark .emp-fixed-row-disabled {
        background-color: rgba(255, 255, 255, 0.02);
        color: rgb(107, 114, 128);
    }

    .emp-fixed-day-badge {
        display: inline-block;
        padding: 0.15rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .emp-fixed-day-badge--active {
        background-color: rgba(59, 130, 246, 0.12);
        color: rgb(37, 99, 235);
    }

    .dark .emp-fixed-day-badge--active {
        background-color: rgba(96, 165, 250, 0.16);
        color: rgb(147, 197, 253);
    }

    .emp-fixed-day-badge--disabled {
        background-color: rgba(156, 163, 175, 0.18);
        color: rgb(107, 114, 128);
    }

    .dark .emp-fixed-day-badge--disabled {
        background-color: rgba(255, 255, 255, 0.06);
        color: rgb(156, 163, 175);
    }
</style>

<div class="emp-fixed-schedule-wrap">
    <table class="emp-fixed-schedule-table">
        <thead>
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('fields.short_check_in_time') }}</th>
                    <th>{{ __('fields.short_break_start_time') }}</th>
                    <th>{{ __('fields.short_break_end_time') }}</th>
                    <th>{{ __('fields.short_check_out_time') }}</th>
                    <th>{{ __('fields.short_check_in_tolerance') }}</th>
                    <th>{{ __('fields.short_check_out_tolerance') }}</th>
                </tr>
            </thead>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            <tr class="{{ $row['is_working'] ? 'emp-fixed-row-active' : 'emp-fixed-row-disabled' }}">
                <td>
                    <span class="emp-fixed-day-badge {{ $row['is_working'] ? 'emp-fixed-day-badge--active' : 'emp-fixed-day-badge--disabled' }}">
                        {{ $row['day_label'] }}
                    </span>
                </td>
                <td>{{ $row['check_in'] }}</td>
                <td>{{ $row['break_start'] }}</td>
                <td>{{ $row['break_end'] }}</td>
                <td>{{ $row['check_out'] }}</td>
                <td>{{ $row['tol_in'] }}</td>
                <td>{{ $row['tol_out'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>