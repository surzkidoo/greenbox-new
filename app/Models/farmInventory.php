<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class farmInventory extends Model
{

    use HasFactory;

    protected $fillable = ['sold', 'death', 'purchase', 'birth', 'farm_id'];

}
