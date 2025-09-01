<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class subscriptionPlan extends Model
{
    //
    protected $fillable = [
        'plan_name',
        'price',
        'description',
        'is_active',
        'duration_days'
    ];

    public function subscriptionUsers()
    {
        return $this->hasMany(subscriptionUser::class);
    }

    public function activeSubscriptionUsers()
    {
        return $this->hasMany(subscriptionUser::class)->where('status', 'active');
    }

    public function deactivatedSubscriptionUsers()
    {
        return $this->hasMany(subscriptionUser::class)->where('status', 'deactivated');
    }

    public function getActiveSubscriptionUsersCount()
    {
        return $this->activeSubscriptionUsers()->count();
    }

    public function getDeactivatedSubscriptionUsersCount()
    {
        return $this->deactivatedSubscriptionUsers()->count();
    }

    public function getActiveSubscriptionUsers()
    {
        return $this->activeSubscriptionUsers()->get();
    }
}
