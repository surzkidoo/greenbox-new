<?php

namespace App\Models;

use App\Models\order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class payment extends Model
{

    use HasFactory;

    protected $guarded = [];


    public function payment()
    {
        return $this->belongsTo(order::class);
    }
}
