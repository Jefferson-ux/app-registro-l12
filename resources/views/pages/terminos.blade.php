<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones</title>
    <link rel="stylesheet" href="{{ asset('css/app/main.css') }}">
    <style>
        :root {
            --bg-body: #f9fafb;
            --bg-card: #ffffff;
            --border-card: #f3f4f6;
            --text-title: #111827;
            --text-body: #4b5563;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-body);
            line-height: 1.6;
            padding: 1rem;
        }
        .container {
            max-width: 48rem;
            margin: 0 auto;
            background-color: var(--bg-card);
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            padding: 2rem;
            border: 1px solid var(--border-card);
        }
        h1 { color: var(--text-title); font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; }
        h2 { color: var(--text-title); font-size: 1.125rem; font-weight: 600; margin: 1.5rem 0 0.75rem; }
        p { margin-bottom: 1rem; }
        ul { margin: 0.5rem 0 1rem 1.25rem; }
        li { margin-bottom: 0.4rem; }
        .back {
            display: inline-block;
            margin-top: 1.5rem;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Términos y Condiciones</h1>
        <p>Estos términos regulan el uso de la plataforma de registro de inquilinos y arrendadores.</p>

        <h2>1. Aceptación</h2>
        <p>Al registrarse y aceptar estas condiciones, usted declara ser mayor de edad y tener capacidad para contratar.</p>

        <h2>2. Uso de la plataforma</h2>
        <p>El usuario se compromete a proporcionar información veraz y a no utilizar la plataforma para fines ilícitos.</p>

        <h2>3. Cuenta y credenciales</h2>
        <p>Las credenciales de acceso son personales e intransferibles. El usuario es responsable de su custodia.</p>

        <h2>4. Limitación de responsabilidad</h2>
        <p>La plataforma no se responsabiliza de los daños derivados del uso indebido o de la falta de veracidad de los datos suministrados.</p>

        <h2>5. Modificaciones</h2>
        <p>Nos reservamos el derecho de modificar estos términos en cualquier momento, notificándolo a los usuarios registrados.</p>

        <a href="{{ url('/') }}" class="back">Volver al inicio</a>
    </div>
</body>
</html>
