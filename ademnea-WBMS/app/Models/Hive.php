<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'apiary_id',
        'hybrid_identifier',
        'display_name',
        'hive_type',
        'construction_material',
        'installation_date',
        'colony_origin',
        'queen_status',
        'latitude',
        'longitude',
        'current_status',
        'last_inspection_date',
        'notes',
    ];

    protected $casts = [
        'installation_date'     => 'date',
        'last_inspection_date'  => 'date',
        'latitude'              => 'decimal:8',
        'longitude'             => 'decimal:8',
        'deleted_at'            => 'datetime',
    ];

    public function apiary(): BelongsTo
    {
        return $this->belongsTo(Apiary::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(HiveStatusHistory::class)
            ->orderBy('transitioned_at', 'desc');
    }

<<<<<<< HEAD
    // TODO: Uncomment when Inspection model is implemented
    // public function inspections(): HasMany
    // {
    //     return $this->hasMany(Inspection::class);
    // }

    // TODO: Uncomment when HarvestRecord model is implemented
    // public function harvestRecords(): HasMany
    // {
    //     return $this->hasMany(HarvestRecord::class);
    // }

    // TODO: Uncomment when AlertThreshold model is implemented
    // public function alertThresholds(): HasMany
    // {
    //     return $this->hasMany(AlertThreshold::class);
    // }

    // TODO: Uncomment when IotDevice model is implemented
    // public function iotDevices(): HasMany
    // {
    //     return $this->hasMany(IotDevice::class);
    // }


    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    // TODO: Uncomment when HarvestRecord model is implemented
    // public function harvestRecords(): HasMany
    // {
    //     return $this->hasMany(HarvestRecord::class);
    // }

    // TODO: Uncomment when AlertThreshold model is implemented
    // public function alertThresholds(): HasMany
    // {
    //     return $this->hasMany(AlertThreshold::class);
    // }

    // TODO: Uncomment when IotDevice model is implemented
    // public function iotDevices(): HasMany
    // {
    //     return $this->hasMany(IotDevice::class);
    // }

>>>>>>> 4a09e20 (APM: redesign core domain models and schema)
    public function scopeActive($query)
    {
        return $query->where('current_status', 'Active');
    }

    public function scopeByApiary($query, int $apiaryId)
    {
        return $query->where('apiary_id', $apiaryId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeNeedingInspection($query, int $daysSinceLastInspection = 30)
    {
        return $query->where(function ($q) use ($daysSinceLastInspection) {
            $q->whereNull('last_inspection_date')
              ->orWhere('last_inspection_date', '<', now()->subDays($daysSinceLastInspection));
        });
    }

    public function getLatestStatusHistory()
    {
        return $this->statusHistory()->latest('transitioned_at')->first();
    }

    public function getDaysSinceLastInspection(): ?int
    {
        $latest = $this->getLatestStatusHistory();

        return $latest ? $latest->transitioned_at->diffInDays(now()) : null;
    }

    public function getSeasonalHarvestTotal(?int $year = null): float
    {
        $query = $this->harvestRecords();

        if ($year) {
            $query->whereYear('harvest_date', $year);
        }

        return (float) $query->sum('honey_yield_kg');
    }

    public function isActive(): bool
    {
        return $this->current_status === 'Active';
    }
}
