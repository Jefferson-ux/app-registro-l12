<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use Illuminate\Mail\Mailables\Address; // Address -->

class ExampleMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $plainPassword
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'This is an Example E-Mail',

            from: new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))



            /*from : {
                 'address' => env('MAIL_FROM_ADDRESS','no-reply@my_platform.com'),
                 'name' => env('MAIL_FROM_NAME','My Platform'),
            },

            replyTo => {
                'address' => 'RecursosHumanos@gmail.com',
                'name' => 'Pedro',
            }*/

        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.email_registration',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
