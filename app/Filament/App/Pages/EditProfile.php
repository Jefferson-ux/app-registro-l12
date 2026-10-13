<?php

namespace App\Filament\App\Pages;

use App\Notifications\SendPasswordResetOtp;
use Filament\Auth\Pages\EditProfile as FilamentEditProfile;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditProfile extends FilamentEditProfile
{
    protected string $view = 'filament.pages.edit-profile';

    // Variables de estado para el flujo de dos pasos
    public ?string $name = null;
    public bool $isEditingName = false;
    public ?string $current_password = null;
    public ?string $new_password = null;
    public ?string $new_password_confirmation = null;
    public ?string $otp_code = null;
    public bool $otpSent = false;

    public function mount(): void
    {
        $this->name = $this->getUser()->name;
    }

    public function startNameEdit(): void
    {
        $this->name = $this->getUser()->name;
        $this->isEditingName = true;
        $this->resetValidation('name');
    }

    public function saveName(): void
    {
        $this->name = trim($this->name ?? '');

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
                'regex:/^[\\p{L}\\p{M}]+(?:[ \'-][\\p{L}\\p{M}]+)*$/u',
            ],
        ], [
            'name.required' => __('El nombre es obligatorio.'),
            'name.min' => __('El nombre debe tener al menos 2 caracteres.'),
            'name.max' => __('El nombre no puede superar 150 caracteres.'),
            'name.regex' => __('Usa solo letras, espacios, guiones y apóstrofes.'),
        ]);

        $user = $this->getUser();
        $user->name = $validated['name'];
        $user->save();

        $this->name = $user->name;
        $this->isEditingName = false;

        Notification::make()
            ->title(__('Nombre actualizado'))
            ->success()
            ->send();
    }

    public function cancelNameEdit(): void
    {
        $this->name = $this->getUser()->name;
        $this->isEditingName = false;
        $this->resetValidation('name');
    }

    /**
     * Paso A: Validar clave actual y enviar el código OTP por correo
     */
    public function requestPasswordChangeOtp(): void
    {
        $user = auth()->user();

        // 1. Validar la contraseña actual
        if (! Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('La contraseña actual es incorrecta.'),
            ]);
        }

        // 2. Validar que la nueva contraseña cumpla con los requisitos
        if (empty($this->new_password) || $this->new_password !== $this->new_password_confirmation) {
            throw ValidationException::withMessages([
                'new_password_confirmation' => __('Las contraseñas no coinciden.'),
            ]);
        }

        // 3. Generar OTP de 6 dígitos y guardarlo en sesión con expiración (10 min)
        $otp = (string) random_int(100000, 999999);
        session([
            'password_change_otp' => $otp,
            'password_change_expires_at' => now()->addMinutes(10),
            'pending_new_password' => Hash::make($this->new_password),
        ]);

        // 4. Enviar correo electrónico
        $user->notify(new SendPasswordResetOtp($otp));

        $this->otpSent = true;

        Notification::make()
            ->title(__('Código enviado'))
            ->body(__('Hemos enviado un código de 6 dígitos a tu correo electrónico.'))
            ->success()
            ->send();
    }

    /**
     * Paso B: Verificar el código OTP e invalidar sesiones activas
     */
    public function confirmPasswordChange(): void
    {
        $sessionOtp = session('password_change_otp');
        $expiresAt = session('password_change_expires_at');
        $pendingPassword = session('pending_new_password');

        if (! $sessionOtp || ! $expiresAt || now()->greaterThan($expiresAt)) {
            $this->resetOtpState();
            throw ValidationException::withMessages([
                'otp_code' => __('El código ha expirado. Solicita uno nuevo.'),
            ]);
        }

        if ($this->otp_code !== $sessionOtp) {
            throw ValidationException::withMessages([
                'otp_code' => __('El código de verificación es incorrecto.'),
            ]);
        }

        // Actualizar la contraseña en la base de datos
        $user = auth()->user();
        $user->update([
            'password' => $pendingPassword,
        ]);

        // Limpiar variables temporales de sesión
        $this->resetOtpState();

        // Cerrar otras sesiones activas (si usas Illuminate\Session\Middleware\AuthenticateSession)
        //auth()->logoutOtherDevices($this->new_password);

        Notification::make()
            ->title(__('Contraseña actualizada'))
            ->body(__('Tu contraseña ha sido cambiada exitosamente.'))
            ->success()
            ->send();
    }

    private function resetOtpState(): void
    {
        session()->forget(['password_change_otp', 'password_change_expires_at', 'pending_new_password']);
        $this->otpSent = false;
        $this->current_password = null;
        $this->new_password = null;
        $this->new_password_confirmation = null;
        $this->otp_code = null;
    }
}
