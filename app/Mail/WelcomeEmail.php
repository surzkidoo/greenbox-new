<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class WelcomeEmail extends Mailable
{

    public function __construct()
    {
    }

    public function build()
    {
        return $this->subject('Welcome to Hibgreenbox')->view('emails.welcome_email');
    }
}

