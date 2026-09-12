<!DOCTYPE html>
<!-- Ubicación: resources/views/dashboard.blade.php -->
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puntual — Asistencia de tu equipo</title>
    <link rel="stylesheet" href="{{ asset('css/app/main.css') }}">
</head>

<body>

    <main class="gateway">

        <section class="panel panel--ink">
            <div class="panel__inner">
                <div class="brand">
                    <span class="brand__mark" aria-hidden="true"></span>
                    <span class="brand__name">Puntual</span>
                </div>

                <h1 class="headline">
                    El tiempo de tu<br>equipo, con exactitud.
                </h1>

                <p class="lede">
                    Puntual registra entradas, salidas y horarios de cada
                    colaborador, para que la planilla nunca dependa de una
                    hoja de cálculo.
                </p>

                <div class="clock" aria-hidden="true">
                    <div class="clock__face">
                        @for ($i = 0; $i < 12; $i++)
                            <span class="clock__tick" style="--i: {{ $i }}"></span>
                            @endfor
                            <span class="clock__hand clock__hand--hour"></span>
                            <span class="clock__hand clock__hand--minute"></span>
                            <span class="clock__hand clock__hand--second"></span>
                            <span class="clock__pin"></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel panel--paper">
            <div class="access-card">
                <p class="access-card__eyebrow">Bienvenido de vuelta</p>

                <h2 class="access-card__title">
                    Ingresa a gestionar la asistencia de tu empresa.
                </h2>

                <a href="{{ url('/app') }}" class="button">
                    Entrar a la app
                </a>
                <br>
                <a href="{{ url('/app/register') }}" class="button">
                    Registrarse
                </a>

                <a href="mailto:soporte@puntual.pe" class="help-link">
                    ¿Necesitas ayuda? Escríbenos
                </a>
            </div>
        </section>

    </main>

</body>

</html>