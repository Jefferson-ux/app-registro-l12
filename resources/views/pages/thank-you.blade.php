<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias por registrarte!</title>
    <style>
        :root {
            --bg-body: #f9fafb;
            --bg-card: #ffffff;
            --border-card: #f3f4f6;
            --text-title: #111827;
            --text-body: #4b5563;
            --icon-bg: #d1fae5;
            --icon-color: #059669;
            --btn-bg: #2563eb;
            --btn-hover: #1d4ed8;
            --btn-text: #ffffff;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: #111827;
                --bg-card: #1f2937;
                --border-card: #374151;
                --text-title: #ffffff;
                --text-body: #d1d5db;
                --icon-bg: rgba(6, 78, 59, 0.4);
                --icon-color: #34d399;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-body);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .card {
            max-width: 28rem;
            width: 100%;
            background-color: var(--bg-card);
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            padding: 2rem;
            text-align: center;
            border: 1px solid var(--border-card);
        }

        .icon-container {
            margin: 0 auto 1rem auto;
            width: 4rem;
            height: 4rem;
            background-color: var(--icon-bg);
            color: var(--icon-color);
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon {
            width: 2rem;
            height: 2rem;
        }

        .title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 1rem;
        }

        .description {
            font-size: 0.875rem;
            color: var(--text-body);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .btn-container {
            padding-top: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--btn-text);
            background-color: var(--btn-bg);
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background-color 0.2s ease-in-out;
        }

        .btn:hover {
            background-color: var(--btn-hover);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-container">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h2 class="title">¡Gracias por su registro!</h2>
        
        <p class="description">
            Hemos recibido los datos de su empresa correctamente. Más adelante se le enviará un correo electrónico con su usuario y credenciales de acceso.
        </p>

        <div class="btn-container">
            <a href="{{ url('/') }}" class="btn">
                Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>