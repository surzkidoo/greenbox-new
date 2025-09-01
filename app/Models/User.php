<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Driver;
use App\Models\wallet;
use App\Models\product;
use App\Models\Vehicle;
use App\Models\vendBank;
use App\Models\permission;
use App\Models\vendProduct;
use App\Models\vendBusiness;
use App\Models\EmailVerification;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'phone',
        'email',
        'address',
        'occupation',
        'state',
        'lga',
        'gender',
        'account_status',
        'fis_verified',
        'email_verified',
        'refer_by',
        'password', // Ensure that the password is always hashed before saving
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function blog()
    {
        return $this->hasMany(blog::class);
    }

    public function product()
    {
        return $this->hasMany(product::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(permission::class,'userpermissions');
    }

        public function logisticbio()
    {
        return $this->hasOne(logisticBio::class);
    }

    public function driver()
    {
        return $this->hasMany(Driver::class);
    }


    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function emailVerification()
    {
        return $this->hasOne(EmailVerification::class, 'email', 'email');
    }


    public function vendBusiness()
    {
        return $this->hasOne(vendBusiness::class);
    }
    public function vendProduct()
    {
        return $this->hasOne(vendProduct::class);
    }

    public function vendBank()
    {
        return $this->hasOne(vendBank::class);
    }

    public function fisBio()
    {
        return $this->hasOne(fis_bio::class);
    }

    public function fisBank()
    {
        return $this->hasOne(fis_bank::class);
    }

    public function fisFarm()
    {
        return $this->hasOne(fis_farm::class);
    }

    public function fisNextkind()
    {
        return $this->hasOne(fis_nextkind::class);
    }

    public function fisGuarantor()
    {
        return $this->hasOne(fis_guarantor::class);
    }

    public function wallet()
    {
        return $this->hasOne(wallet::class);
    }

    //subscription relationship
    public function subscriptions()
    {
        return $this->hasMany(subscriptionUser::class);
    }


}
