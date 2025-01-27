<?php

namespace App\Models;

use App\Models\FarmType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Benefit extends Model
{

    use HasFactory;



    protected $guarded = [];


    public function farmType()
    {
        return $this->belongsTo(FarmType::class);
    }
}
