<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad</title>
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
        <h1>Política de Privacidad</h1>
        <p>En esta política le explicamos cómo tratamos y protegemos sus datos personales al registrarse en nuestra plataforma.</p>

        <h2>1. Responsable del tratamiento</h2>
        <p>Los datos recogidos son tratados por la plataforma de registro de inquilinos, con el fin de gestionar el alta de inquilinos y arrendadores.</p>

        <h2>2. Finalidad</h2>
        <p>Sus datos se utilizan para crear y gestionar su cuenta de inquilino/arrendador, comunicarle novedades y enviarle credenciales de acceso.</p>

        <h2>3. Derechos</h2>
        <p>Puede acceder, rectificar, cancelar u oponerse al tratamiento de sus datos escribiendo a la dirección de contacto indicada en el aviso legal.</p>

        <h2>4. Conservación</h>
        <p>Conservamos sus datos mientras sea necesario para la finalidad para la que fueron recabados o mientras exista una obligación legal.</p>

        <a href="{{ url('/') }}" class="back">Volver al inicio</a>
    </div>
</body>
</html>
