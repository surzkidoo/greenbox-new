<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Templete extends Mailable
{
    use Queueable, SerializesModels;

    public $message;
    public $header;
    public $subject;
    public $footer;
    public $btnValue;
    public $btnUrl;

    public function __construct($message, $header, $subject, $footer, $btnValue = 'Click Here', $btnUrl = null)
    {
        $this->message = $message;
        $this->header = $header;
        $this->subject = $subject;
        $this->footer = $footer;
        $this->btnValue = $btnValue;
        $this->btnUrl = $btnUrl;
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.templete')
                    ->with([
                        'emailMessage' => $this->message,
                        'header' => $this->header,
                        'footer' => $this->footer,
                        'btnValue' => $this->btnValue,
                        'btnUrl' => $this->btnUrl
                    ]);
    }
}
