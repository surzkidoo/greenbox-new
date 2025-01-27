<?php

namespace App\Models;

use App\Models\User;
use App\Models\farmTask;
use App\Models\FarmType;
use App\Models\farmInventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class farms extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function task()
    {
        return $this->hasMany(farmTask::class,'farm_id');
    }

    public function type()
    {
        return $this->hasOne(FarmType::class,'id');
    }


    public function inventory()
    {
        return $this->belongsTo(farmInventory::class);
    }

}
