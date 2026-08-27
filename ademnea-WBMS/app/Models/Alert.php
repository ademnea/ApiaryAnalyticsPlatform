<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'farmer_id',
        'hive_id',
        'type',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'farmer_id'   => 'integer',
        'hive_id'     => 'integer',
        'is_read'     => 'boolean',
        'created_at'  => 'datetime',
        'read_at'     => 'datetime',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public function isCooldownExempt(): bool
    {
        return $this->type === 'malfunction';
    }
}
