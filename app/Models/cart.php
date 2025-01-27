<?php

namespace App\Models;

use App\Models\cartItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class cart extends Model
{

    use HasFactory;

    protected $fillable = ['user_id', 'session_id'];

    public function items()
    {
        return $this->hasMany(cartItem::class);
    }

}
