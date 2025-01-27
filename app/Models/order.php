<?php

namespace App\Models;

use App\Models\address;
use App\Models\payment;
use App\Models\shipping;
use App\Models\orderItems;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class order extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(user::class);
    }

    public function items()
    {
        return $this->hasMany(orderItems::class);
    }

    public function billingAddress()
    {
        return $this->belongsTo(address::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(address::class, 'shipping_address_id');
    }

    public function shipping()
    {
        return $this->hasOne(shipping::class);
    }

    public function payment()
    {
        return $this->hasOne(payment::class);
    }
}
