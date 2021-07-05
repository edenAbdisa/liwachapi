<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportType extends Model
{
    use HasFactory;
    protected $fillable = [
        'report_detail',
        'type_for'
    ];
    public function flag()
    {
        return $this->hasMany(Flag::class);
    }
}
