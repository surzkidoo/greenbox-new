<?php

namespace App\Models;

use App\Models\address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class setting extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function defaultShippingAddress()
{
    return $this->belongsTo(address::class, 'default_shipping');
}
}
