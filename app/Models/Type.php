<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'category_id',
        'status',
        'used_for'
    ];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function serviceSwapType()
    {
        return $this->hasMany(ServiceSwapType::class);
    }
    public function itemSwapType()
    {
        return $this->hasMany(ItemSwapType::class);
    }
    /*
    public function itemSwap()
    {
        return $this->belongsToMany(Item::class);
    } 
    public function serviceSwap()
    {
        return $this->belongsToMany(Service::class);
    }*/
    public function item()
    {
        return $this->hasMany(Item::class);
    }
    public function service()
    {
        return $this->hasMany(Service::class);
    }
    public function subscription()
    {
        return $this->hasMany(Subscription::class);
    }
}
