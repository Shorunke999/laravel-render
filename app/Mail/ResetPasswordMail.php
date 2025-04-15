<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    /**
     * Create a new message instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

        /**

     * Get the message content definition.

     */

     public function content(): Content

     {

         return new Content(

             view: 'emails.reset-password',

             with: [

                 'token' => $this->token,

                 'email' => $this->email,

             ],

         );

     }
    /**
     * Build the message.
     */
    public function build()
    {
        return $this->markdown('emails.reset-password')
                    ->subject('Reset Password Notification');
    }
}
