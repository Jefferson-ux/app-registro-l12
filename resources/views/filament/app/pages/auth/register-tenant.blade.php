<x-filament-panels::page.simple>
    <style>
        .custom-form-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .login-link-box {
            text-align: center;
            font-size: 0.875rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .login-link-text {
            color: #6b7280;
        }

        .login-link {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            margin-left: 0.25rem;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .btn {
            width: 75%;
            margin: auto;
        }

.fi-fo-field-label-required-mark {
    display: none;
}


    </style>

    <div class="custom-form-container">
        <form wire:submit="register" class="custom-form-container">
            {{ $this->form }}

            <x-filament::button type="submit" class="btn">
                Registrar Empresa
            </x-filament::button>
        </form>

        <div class="login-link-box">
            <span class="login-link-text">¿Ya tienes una cuenta?</span>
            <a href="{{ filament()->getLoginUrl() }}" class="login-link">
                Iniciar sesión
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('livewire:navigated', initLocation);
    document.addEventListener('DOMContentLoaded', initLocation);

    function initLocation() {
        const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        // Esperar a que el objeto global de Livewire cargue el componente
        const checkLivewire = setInterval(() => {
            const component = document.querySelector('[wire\\:id]');
            
            if (component && window.Livewire) {
                clearInterval(checkLivewire);
                const wire = window.Livewire.find(component.getAttribute('wire:id'));

                if (wire) {
                    // Setear la Zona Horaria
                    if (userTimezone) {
                        wire.set('data.timezone', userTimezone);
                    }

                    // Autodetectar el País silenciosamente por IP
                    fetch('https://ipapi.co/json/')
                        .then(response => response.json())
                        .then(data => {
                            if (data.country_code) {
                                wire.set('data.country', data.country_code);
                            }
                        })
                        .catch(() => {});
                }
            }
        }, 100);
    }
</script>
</x-filament-panels::page.simple>