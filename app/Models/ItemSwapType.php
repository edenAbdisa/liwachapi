<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemSwapType extends Model
{
    use HasFactory;
    protected $fillable = [
        'type_id',
        'item_id'
    ];
}
