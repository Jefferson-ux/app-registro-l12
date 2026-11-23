<?php

namespace App\Filament\Widgets\Attendance;

use App\Models\AttendanceRecord;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AttendanceRecordsChart extends ChartWidget
{
    protected static ?int $sort = 50;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        return 'Registros de asistencia recientes';
    }

    protected function getData(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        $data = collect(range(6, 0))->map(function ($daysAgo) use ($tenantId) {
            $date = Carbon::now()->subDays($daysAgo)->startOfDay();

            return [
                'day'   => $date->translatedFormat('d M'),
                'count' => AttendanceRecord::where('tenant_id', $tenantId)
                    ->whereDate('recorded_at', $date)
                    ->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Registros',
                    'data'            => $data->pluck('count')->toArray(),
                    'borderColor'     => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $data->pluck('day')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
