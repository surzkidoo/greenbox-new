<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class walletTransaction extends Model
{

    use HasFactory;

    protected $guarded = [];


    public function wallet()
    {
        return $this->belongsTo(wallet::class);
    }

}
