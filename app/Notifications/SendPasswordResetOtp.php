<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendPasswordResetOtp extends Notification
{
    use Queueable;

    public function __construct(public string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Código de confirmación para cambio de contraseña'))
            ->greeting(__('Hola :name,', ['name' => $notifiable->name]))
            ->line(__('Has solicitado cambiar tu contraseña en la plataforma.'))
            ->line(__('Tu código de verificación de 6 dígitos es:'))
            ->line("**{$this->otp}**")
            ->line(__('Este código expirará en 10 minutos.'))
            ->line(__('Si no solicitaste este cambio, ignora este mensaje o contacta a soporte.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
