<x-filament-widgets::widget>
    <x-filament::section>
        <div class="custom-legend-wrapper">
            <style>
                .custom-legend-wrapper {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    gap: 1.5rem;
                }
                .custom-legend-title {
                    font-size: 0.875rem;
                    font-weight: 600;
                    margin: 0;
                }
                .custom-legend-list {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 1.25rem;
                    list-style: none;
                    margin: 0;
                    padding: 0;
                }
                .custom-legend-item {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    font-size: 0.8125rem;
                }
                .custom-legend-indicator {
                    width: 0.875rem;
                    height: 0.875rem;
                    border-radius: 0.25rem;
                    border: 1px solid rgba(0, 0, 0, 0.15);
                }
                .indicator-deleted {
                    background-color: #f3f4f6;
                    border-color: #d1d5db;
                }
                .dark .indicator-deleted {
                    background-color: #1f2937;
                    border-color: #374151;
                }
                @media (max-width: 640px) {
                    .custom-legend-wrapper {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 0.75rem;
                    }
                }
            </style>

            <h3 class="custom-legend-title">Leyenda:</h3>
            
            <ul class="custom-legend-list">
                <li class="custom-legend-item">
                    <span class="custom-legend-indicator indicator-deleted"></span>
                    <span>Si la fila está apagada es Empleado Inactivo (Soft Deleted)</span>
                </li>
            </ul>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>