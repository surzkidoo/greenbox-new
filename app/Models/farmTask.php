<?php

namespace App\Models;

use App\Models\farms;
use App\Models\farmActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class farmTask extends Model
{

    use HasFactory;


    protected $guarded = [];



    public function activity()
{
    return $this->belongsTo(FarmActivity::class, 'farm_activities_id');
}

        public function farm()
    {
        return $this->belongsTo(farms::class, 'farm_id');
    }
}
