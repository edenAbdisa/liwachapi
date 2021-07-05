<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestOrder extends Model
{
    use HasFactory;
    protected $table = 'requests';
    protected $fillable = [
        'status',
        'requester_id',
        'requested_item_id',
        'requester_item_id',
        'rating',
        'token',
        'type'
    ];
    public function requester()
    {
        return $this->belongsTo(User::class);
    }
    public function requested_item()
    {
        if($this->type =='service'){
            return $this->belongsTo(Service::class);
        }else{
            return $this->belongsTo(Item::class);
        }
    }
    public function requester_item()
    {
        if($this->type =='service'){
            return $this->belongsTo(Service::class);
        }else{
            return $this->belongsTo(Item::class);
        }
    }
    public function message()
    {
        return $this->hasMany(Message::class);
    }
}
