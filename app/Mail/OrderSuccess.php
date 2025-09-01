<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class OrderSuccess extends Mailable
{
    public $order;

    public function __construct($order)
    {
        $this->order = $order; // Assuming you pass the order data to the view
    }


    public function build()
    {
        return $this->subject('Order Summary')->view('emails.order_confirm_email')->with([
            'order' => $this->order, // Assuming you pass the order data to the view

        ]);
    }
}

