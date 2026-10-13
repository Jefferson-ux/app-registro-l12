<?php

namespace App\Http\Controllers;

use App\Mail\ExampleMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class MailController extends Controller
{
    public function mailMe(Request $request)
    {
        // 1. Obtener el nombre del tenant (puedes recibirlo del request o contexto)
        $tenantName = $request->input('tenant_name', 'default-tenant');
        $tenantEmail = $request->input('tenant_name', 'default-tenant');

        // Formato requested: tenant-name+admin
        $username = $tenantName . '-admin';
        $email = 'jose3nmw@gmail.com';

        // 2. Generar contraseña aleatoria de 10 caracteres
        $plainPassword = Str::random(12);

        // 3. Crear el usuario en la BD
        $user = User::create([
            'name'     => $username,
            'email'    => $email,
            'password' => Hash::make($plainPassword),
        ]);

        // 4. Enviar el correo pasando el usuario y la clave en texto plano
        Mail::to($user->email)
            ->send(new ExampleMail($user, $plainPassword));

        return redirect()
            ->route('thank-you')
            ->with('success', '¡Usuario creado y credenciales enviadas correctamente!');
    }
}
