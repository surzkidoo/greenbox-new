<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ResetSuccess extends Mailable
{

    public function __construct()
    {
    }

    public function build()
    {
        return $this->subject('Password Reset Successful')->view('emails.welcome_back_email');
    }
}

