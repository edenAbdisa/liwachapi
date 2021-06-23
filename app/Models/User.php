<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    /* protected String $primaryKey = 'id';
    public String $incrementing = true;
    public Timestamp $timestamps = true;
    public String $firstName ;
    public String $lastName ;
    public String $email;
    public String $profilePicture;
    public String $phoneNumber;
    public String $TINPicture;
    public String $status;
    public Date $birthDate;
    public String $type;
    public BigInt $addressId;
    public Timestamp $emailVerifiedAt;
    public String $token; */ 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
}
