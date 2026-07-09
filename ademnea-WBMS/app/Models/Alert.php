<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    public $timestamps = false; // only created_at, handled manually below

    protected $fillable = [
        'farmer_id',
        'hive_id',
        'type',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
        'read_at'    => 'datetime',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function hive()
    {
        // Hive model is owned by Developer B (Apiary Management).
        // Confirm namespace once their model lands on `development`.
        return $this->belongsTo(Hive::class);
    }

    /**
     * REQ-F-FAPI-24: malfunction alerts are exempt from the 1hr cooldown.
     */
    public function isCooldownExempt(): bool
    {
        return $this->type === 'malfunction';
    }
}
