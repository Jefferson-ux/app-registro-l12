<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Historial de Marcaciones Recientes (Últimos registros)
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
            .badge-status {
                display: inline-block;
                padding: 4px 8px;
                font-size: 12px;
                font-weight: 600;
                border-radius: 4px;
            }
            .badge-present { background-color: #d1fae5; color: #065f46; }
            .badge-late { background-color: #fef3c7; color: #92400e; }
            .badge-absent { background-color: #fee2e2; color: #991b1b; }
        </style>

        <div style="overflow-x: auto; max-height: 400px; overflow-y: auto;">
            <table class="attendance-history-table">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Hora de Marcación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>
                                {{ $session->employee?->first_name ?? 'N/A' }} 
                                {{ $session->employee?->last_name ?? '' }}
                            </td>
                            <td>
                                {{-- Si tienes un formateador personalizado como tu formatHumanTime o el standard de Carbon --}}
                                {{ self::formatHumanTime($session->created_at) }}
                            </td>
                            <td>
                                @php
                                    $status = $session->status;
                                    $label = match($status) {
                                        'present' => 'Temprano / A tiempo',
                                        'late' => 'Tardanza',
                                        'absent' => 'Ausente',
                                        default => ucfirst($status),
                                    };
                                    $class = match($status) {
                                        'present' => 'badge-present',
                                        'late' => 'badge-late',
                                        default => 'badge-absent',
                                    };
                                @endphp
                                <span class="badge-status {{ $class }}">
                                    {{ $label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: #9ca3af; padding: 20px;">
                                No hay registros de asistencia recientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>