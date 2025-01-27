<?php

namespace App\Models;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Driver extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }
}
