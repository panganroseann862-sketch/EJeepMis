<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'location',
        'message',
        'status'
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}