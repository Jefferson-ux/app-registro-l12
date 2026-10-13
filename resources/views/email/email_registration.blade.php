<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tus Credenciales de Acceso</title>
    <style>
        /* Resets de CSS para clientes de correo */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Estilos e interacciones */
        @keyframes floatIcon {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .animated-icon {
            animation: floatIcon 2.5s ease-in-out infinite;
        }

        .btn-primary:hover {
            background-color: #4338ca !important;
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.35) !important;
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155;">

    <!-- Contenedor Principal -->
    <table role="presentation" width="100%" height="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center" valign="top">

                <!-- Tarjeta Central -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">

                    <!-- Encabezado con Degradado Elegante -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 40px 30px; text-align: center;">
                            <div class="animated-icon" style="display: inline-block; background-color: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px); padding: 16px; border-radius: 50%; margin-bottom: 12px;">
                                <span style="font-size: 36px; display: block; line-height: 1;">🔐</span>
                            </div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; line-height: 1.2;">
                                ¡Cuenta Creada Exitosamente!
                            </h1>
                            <p style="color: rgba(255, 255, 255, 0.85); margin: 8px 0 0 0; font-size: 14px; font-weight: 400;">
                                Bienvenido a la plataforma
                            </p>
                        </td>
                    </tr>

                    <!-- Cuerpo del Mensaje -->
                    <tr>
                        <td style="padding: 35px 30px 25px 30px;">
                            <p style="margin: 0 0 16px 0; font-size: 16px; color: #1e293b; font-weight: 600;">
                                Hola {{ $user->name }},
                            </p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; color: #64748b; line-height: 1.6;">
                                Se ha registrado tu empresa en el sistema. A continuación encontrarás las credenciales asignadas para acceder a tu panel de administración:
                            </p>

                            <!-- Cuestionario / Credenciales Box -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #4f46e5; border-radius: 12px; padding: 20px; margin-bottom: 28px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="padding-bottom: 6px; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Correo Electrónico
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 16px; font-size: 15px; font-weight: 600; color: #0f172a;">
                                            {{ $user->email }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 6px; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Contraseña Temporal
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span style="display: inline-block; background-color: #e0e7ff; color: #3730a3; font-family: 'Courier New', Courier, monospace; font-weight: 700; font-size: 16px; padding: 8px 14px; border-radius: 8px; letter-spacing: 1px;">
                                                {{ $plainPassword }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Botón de Acción Principal (CTA) -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/app/login') }}" class="btn-primary" style="background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; display: inline-block; transition: all 0.2s ease-in-out; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);">
                                            Acceder al Panel
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Pie de página -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #f1f5f9; padding: 20px 30px; text-align: center;">
                            <p style="margin: 0 0 6px 0; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                                💡 <strong>Nota de seguridad:</strong> Te sugerimos actualizar tu contraseña una vez ingreses por primera vez.
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #cbd5e1;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>