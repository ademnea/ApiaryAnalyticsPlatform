<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Hive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'apiary_id',
        'display_name',
        'hive_type',
        'construction_material',
        'installation_date',
        'colony_origin',
        'queen_status',
        'status',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy_meters',
        'last_inspection_date',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_inspection_date' => 'date',
        'gps_latitude' => 'float',
        'gps_longitude' => 'float',
        'gps_accuracy_meters' => 'integer',
    ];

    /**
     * Relationship: A hive belongs to an apiary.
     */
    public function apiary(): BelongsTo
    {
        return $this->belongsTo(Apiary::class);
    }

    /**
     * Relationship: A hive has many status history records.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(HiveStatusHistory::class);
    }

    /**
     * Relationship: A hive has many inspection records.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Relationship: A hive has many harvest records.
     */
    public function harvestRecords(): HasMany
    {
        return $this->hasMany(HarvestRecord::class);
    }

    /**
     * Relationship: A hive has many IoT devices assigned to it.
     * Note: This assumes hive_device_assignments table with direct FK.
     * If using a pivot table, this would be a belongsToMany.
     */
    public function iotDevices(): HasMany
    {
        return $this->hasMany(IotDevice::class);
    }

    /**
     * Convenience relationship: All temperature readings from devices on this hive.
     */
    public function temperatureReadings(): HasManyThrough
    {
        return $this->hasManyThrough(
            HiveTemperature::class,
            IotDevice::class,
            'hive_id', // FK on iot_devices
            'device_id' // FK on hive_temperatures
        );
    }

    /**
     * Convenience relationship: All humidity readings.
     */
    public function humidityReadings(): HasManyThrough
    {
        return $this->hasManyThrough(
            HiveHumidity::class,
            IotDevice::class,
            'hive_id',
            'device_id'
        );
    }

    /**
     * Scope: Get only active hives.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Filter by apiary.
     */
    public function scopeByApiary($query, int $apiaryId)
    {
        return $query->where('apiary_id', $apiaryId);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Hives that need inspection (last inspection was X days ago).
     * Example: days ago since last inspection.
     */
    public function scopeNeedingInspection($query, int $daysSinceLastInspection = 30)
    {
        return $query->where(function ($q) use ($daysSinceLastInspection) {
            $q->whereNull('last_inspection_date')
              ->orWhere('last_inspection_date', '<', now()->subDays($daysSinceLastInspection));
        });
    }

    /**
     * Get the most recent status from history.
     */
    public function getLatestStatusHistory()
    {
        return $this->statusHistory()->latest('created_at')->first();
    }
}