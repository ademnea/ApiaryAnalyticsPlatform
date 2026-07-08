<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apiary extends Model
{
    // SECURITY: Use $guarded instead of $fillable
    protected $guarded = [
        'id',
        'is_active',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hive_capacity' => 'integer',
    ];

    /**
     * Relationship: An apiary has many hives.
     */
    public function hives(): HasMany
    {
        return $this->hasMany(Hive::class);
    }

    /**
     * Relationship: An apiary belongs to a farmer.
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class, 'farmer_id');
    }

    /**
     * Convenience relationship: All IoT devices assigned to hives in this apiary.
     * This is a hasManyThrough relationship that chains through hives.
     */
    public function iotDevices(): HasManyThrough
    {
        return $this->hasManyThrough(IotDevice::class, Hive::class);
    }

    /**
     * Scope: Get only active apiaries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by country.
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }
}