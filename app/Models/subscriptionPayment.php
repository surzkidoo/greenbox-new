<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class subscriptionPayment extends Model
{
    //

    protected $fillable = [
        'subscription_user_id',
        'amount',
        'payment_method',
        'transaction_id'
    ];

    public function subscriptionUser()
    {
        return $this->belongsTo(subscriptionUser::class);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }


}
