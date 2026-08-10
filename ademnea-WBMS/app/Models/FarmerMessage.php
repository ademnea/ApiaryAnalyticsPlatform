<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'hive_id',
        'subject',
        'message',
        'status',
    ];

    protected $casts = [
        'farmer_id'    => 'integer',
        'hive_id'      => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }
}
