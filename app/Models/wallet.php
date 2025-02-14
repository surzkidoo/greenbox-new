<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class wallet extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
