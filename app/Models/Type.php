<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'category_id'
    ];    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function itemSwap()
    {
        return $this->belongsToMany(Item::class);
    }
    public function serviceSwap()
    {
        return $this->belongsToMany(Service::class);
    }
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