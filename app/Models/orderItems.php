<?php

namespace App\Models;

use App\Models\order;
use App\Models\product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class orderItems extends Model
{
    protected $guarded = [];

    use HasFactory;


    public function product()
    {
        return $this->belongsTo(product::class);
    }

    public function shipping()
    {
        return $this->hasOne(shipping::class,'order_item_id');
    }

    public function order()
    {
        return $this->belongsTo(order::class);
    }


}
