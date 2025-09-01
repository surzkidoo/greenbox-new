<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class subscriptionUser extends Model
{
    //
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'start_date',
        'end_date',
        'status',
        'transaction_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(subscriptionPlan::class);
    }

    public function payments()
    {
        return $this->hasMany(subscriptionPayment::class);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isDeactivated()
    {
        return $this->status === 'deactivated';
    }

    public function activate()
    {
        $this->status = 'active';
        $this->save();
    }
}
