<?php

namespace App\Models;

use App\Models\product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class productImage extends Model
{

    use HasFactory;

    protected $guarded = [

    ];

    public function product()
    {
        return $this->belongsTo(product::class);
    }
}
