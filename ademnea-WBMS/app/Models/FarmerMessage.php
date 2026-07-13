<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerMessage extends Model
{
    protected $fillable = [
        'farmer_id',
        'hive_id',
        'subject',
        'message',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function hive()
    {
        return $this->belongsTo(Hive::class);
    }
}
