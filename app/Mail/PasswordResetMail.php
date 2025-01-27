<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PasswordResetMail extends Mailable
{
    public $resetUrl;

    public function __construct($resetUrl)
    {
        $this->resetUrl = $resetUrl;
    }

    public function build()
    {
        return $this->subject('Password Reset Request')
                    ->view('emails.password_reset') // Your Blade view
                    ->with([
                        'resetUrl' => $this->resetUrl,
                    ]);
    }
}
