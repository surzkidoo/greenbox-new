<?php

namespace App\Models;

use App\Models\orderItems;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class shipping extends Model
{

    use HasFactory;

    // Relationship to the order item
    public function item()
    {
        return $this->belongsTo(orderItems::class, 'order_item_id');
    }

    // Optionally, add a relationship to the logistic (user)
    public function logistic()
    {
        return $this->belongsTo(User::class, 'logistic_id');
    }


}
