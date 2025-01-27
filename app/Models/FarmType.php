<?php

namespace App\Models;

use App\Models\Benefit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FarmType extends Model
{

    use HasFactory;

    protected $guarded = [];


    public function benefit()
    {
        return $this->hasOne(Benefit::class);
    }

}
