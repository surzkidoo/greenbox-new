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

}
