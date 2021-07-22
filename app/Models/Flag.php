<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flag extends Model
{
    use HasFactory;
    protected $fillable = [
        'reason_id',
        'flagged_item_id',
        'flagged_by_id',
        'type'
    ];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];
    public function reason()
    {
        return $this->belongsTo(ReportType::class);
    }
    public function flagged_by()
    {
        return $this->belongsTo(User::class);
    }
    public function flagged_item()
    {
        if ($this->type == 'service') {
            return $this->belongsTo(Service::class);
        } else {
            return $this->belongsTo(Item::class);
        }
    }
}
