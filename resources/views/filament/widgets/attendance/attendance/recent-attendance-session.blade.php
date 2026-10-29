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
            .status-present { color: #16a34a; font-weight: 600; }
            .status-late    { color: #eab308; font-weight: 600; }
            .status-out     { color: #3b82f6; font-weight: 600; }
            .status-other   { color: #9ca3af; font-weight: 600; }
        </style>

        <div style="overflow-x: auto; max-height: 400px; overflow-y: auto;">
            <table class="attendance-history-table">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Marcación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $e)
                        @php
                            $class = match (true) {
                                $e['type'] === 'Salida'    => 'status-out',
                                $e['status'] === 'present' => 'status-present',
                                $e['status'] === 'late'    => 'status-late',
                                default                    => 'status-other',
                            };
                        @endphp
                        <tr>
                            <td>
                                {{ $e['employee']?->first_name ?? 'N/A' }}
                                {{ $e['employee']?->last_name ?? '' }}
                            </td>
                            <td>
                                {{ $e['type'] }} {{ self::formatHumanTime($e['at']) }}
                            </td>
                            <td>
                                <span class="{{ $class }}">
                                    {{ $e['type'] === 'Salida'
                                        ? 'Salida'
                                        : match ($e['status']) {
                                            'present' => 'Puntual',
                                            'late'    => 'Tarde',
                                            default   => $e['status'],
                                        } }}
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