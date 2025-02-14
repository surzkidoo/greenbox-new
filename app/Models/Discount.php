<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount',
        'percentage',
        'is_active',
        'discount_valid',
    ];

    protected $casts = [
        'percentage' => 'boolean',
        'is_active' => 'boolean',
        'discount_valid' => 'date',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_discounts');
    }
}
