<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'picture',
        'status',
        'description',
        'number_of_flag',
        'number_of_request',
        'bartering_location_id',
        'type_id',
        'user_id'
    ];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];

    public function bartering_location()
    {
        return $this->belongsTo(Address::class);
    }
    public function type()
    {
        return $this->belongsTo(Type::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function request()
    {
        return $this->hasMany(RequestOrder::class, 'requested_item_id');
    }
    public function flag()
    {
        return $this->hasMany(Flag::class);
    }
    public function serviceSwapType()
    {
        return $this->hasMany(ServiceSwapType::class);
    }
}
