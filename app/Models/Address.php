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
        'subcity',
        'district',
        'landmark',
        'type'
    ];
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function item()
    {
        return $this->hasOne(Item::class);
    }
}
