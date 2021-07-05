<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'profile_picture',
        'phone_number',
        'TIN_picture',
        'status',
        'birthdate',
        'type',
        'address_id',
        'membership_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }
    public function flag()
    {
        return $this->hasMany(Flag::class);
    }
    public function message()
    {
        return $this->hasMany(Message::class);
    }
    public function request()
    {
        return $this->hasMany(RequestOrder::class);
    }
    public function subscription()
    {
        return $this->hasMany(Subscription::class);
    }
}
