<?php

namespace App\Models;

use App\Models\cart;
use App\Models\product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class cartItem extends Model
{

    use HasFactory;

    protected $fillable = ['cart_id', 'product_id', 'quantity'];

    public function product()
    {
        return $this->belongsTo(product::class);
    }

    // A cart item belongs to a cart
    public function cart()
    {
        return $this->belongsTo(cart::class);
    }
}
