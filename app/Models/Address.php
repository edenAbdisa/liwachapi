<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    /* protected Bigint $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
    public String $country;
    public String $city;
    public String $subcity;
    public String $district;
    public String $landmark;
    public String $type; */
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

}
