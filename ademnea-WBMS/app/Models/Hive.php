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

        // Identifier columns – hybrid_identifier is the model-preferred name;
        // hive_code is the original DB column (kept for backward compat).
        'hybrid_identifier',
        'hive_code',

        'display_name',
        'name',         // Farmer-API column — kept in sync with display_name via booted()
        'hive_type',
        'construction_material',
        'installation_date',
        'colony_origin',
        'queen_status',

        // Status – current_status is model-preferred (added by migration);
        // status is the original DB column (kept for backward compat).
        'current_status',
        'status',

        // GPS – latitude/longitude are model-preferred aliases (added by migration);
        // gps_latitude/gps_longitude are the original DB columns.
        'latitude',
        'longitude',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy_meters',

        'last_inspection_date',
        'notes',
    ];

    protected $casts = [
        'installation_date'     => 'date',
        'last_inspection_date'  => 'date',
        'latitude'              => 'decimal:8',
        'longitude'             => 'decimal:8',
        'gps_latitude'          => 'decimal:8',
        'gps_longitude'         => 'decimal:8',
        'deleted_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Hive $hive): void {
            // hive_code is the original NOT NULL DB column.
            // hybrid_identifier is the model-preferred name set by the service.
            // Keep them in sync so neither violates its constraint.
            if (!empty($hive->hybrid_identifier) && empty($hive->hive_code)) {
                $hive->hive_code = $hive->hybrid_identifier;
            } elseif (!empty($hive->hive_code) && empty($hive->hybrid_identifier)) {
                $hive->hybrid_identifier = $hive->hive_code;
            }

            // name (Farmer-API column) ↔ display_name (admin module column) sync.
            if (!empty($hive->display_name) && empty($hive->name)) {
                $hive->name = $hive->display_name;
            } elseif (!empty($hive->name) && empty($hive->display_name)) {
                $hive->display_name = $hive->name;
            }
        });

        static::updating(function (Hive $hive): void {
            if (!empty($hive->hybrid_identifier) && empty($hive->hive_code)) {
                $hive->hive_code = $hive->hybrid_identifier;
            } elseif (!empty($hive->hive_code) && empty($hive->hybrid_identifier)) {
                $hive->hybrid_identifier = $hive->hive_code;
            }

            if (!empty($hive->display_name) && empty($hive->name)) {
                $hive->name = $hive->display_name;
            } elseif (!empty($hive->name) && empty($hive->display_name)) {
                $hive->display_name = $hive->name;
            }
        });
    }

    public function apiary(): BelongsTo
    {
        return $this->belongsTo(Apiary::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(HiveStatusHistory::class, 'hive_id')
            ->orderBy('transitioned_at', 'desc');
    }

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
