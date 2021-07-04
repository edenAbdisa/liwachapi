<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    protected $fillable = [
        'picture',
        'status',
        'number_of_flag',
        'number_of_request',
        'bartering_location_id',
        'type_id'
    ]; 
}
