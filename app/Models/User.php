<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
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
        'membership_id',
        'remember_token'
    ];
    protected $token='';
    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'email_verified_at' => 'datetime:Y-m-d',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
    */
    protected $hidden = [
        'password'
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
    public function item()
    {
        return $this->hasMany(Item::class);
    }
    public function service()
    {
        return $this->hasMany(Service::class);
    } 
    public function user_transaction()
    {
        return $this->hasMany(UserTransaction::class);
    }
   /*  public function media()
    {
        return $this->hasMany(Media::class, 'item_id');
    } */
}
