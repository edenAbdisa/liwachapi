<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country',
        'city',
        'latitude',
        'longitude',
        'type'
    ];
    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];
    public function user()
    {
        return $this->hasOne(User::class,'address_id');
    }
    public function item()
    {
        return $this->hasOne(Item::class,'bartering_location_id');
    }
    public function service()
    {
        return $this->hasOne(Service::class,'bartering_location_id');
    }
}
