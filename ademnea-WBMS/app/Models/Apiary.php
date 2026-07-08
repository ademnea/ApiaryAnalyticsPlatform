<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Apiary extends Model
{
    protected $fillable = [
        'name',
        'country',
        'region',
        'managing_entity',
        'hive_capacity',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'is_active',
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