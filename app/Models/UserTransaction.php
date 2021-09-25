<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'left_limit_of_post',
        'left_transaction_limit',
        'refreshed_on'
    ];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
