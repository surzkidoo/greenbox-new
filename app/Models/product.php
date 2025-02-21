<?php

namespace App\Models;

use App\Models\User;
use App\Models\productImage;
use App\Models\productCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(productCategory::class,'product_categories_id');
    }

    public function images()
    {
        return $this->hasMany(productImage::class);
    }


    public function discounts()
{
    return $this->belongsToMany(Discount::class, 'product_discounts');
}


public function getPrice()
{
    // If a discounted price is set and less than the original price, return it.
    if ($this->d_price > 0 && $this->d_price < $this->getPrice()) {
        return $this->d_price;
    }

    return $this->getPrice();
}


}
