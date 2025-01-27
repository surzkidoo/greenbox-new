<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class fis_bio extends Model
{
    protected $guarded = [];

    use HasFactory;


    public function farm()
{
    return $this->hasOne(fis_farm::class);
}

public function bank()
{
    return $this->hasOne(fis_bank::class);
}

public function guarantor()
{
    return $this->hasOne(fis_guarantor::class);
}

public function nextOfKin()
{
    return $this->hasOne(fis_nextkind::class);
}

}
