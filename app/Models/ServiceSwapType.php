<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSwapType extends Model
{
    use HasFactory;
    protected $fillable = [
        'type_id',
        'service_id'
    ];
    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}