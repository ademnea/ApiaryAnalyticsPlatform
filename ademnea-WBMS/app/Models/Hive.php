<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hive extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'apiary_id',
        'hybrid_identifier',
        'hive_code',
        'display_name',
        'name',
        'hive_type',
        'construction_material',
        'installation_date',
        'colony_origin',
        'queen_status',
        'current_status',
        'status',
        'latitude',
        'longitude',
        'accuracy_meters',
        'last_inspection_date',
        'notes',
    ];

    protected $casts = [
        'installation_date'     => 'date',
        'last_inspection_date'  => 'date',
        'latitude'              => 'decimal:8',
        'longitude'             => 'decimal:8',
        'accuracy_meters'       => 'decimal:2',
        'deleted_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Hive $hive): void {
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

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function harvestRecords(): HasMany
    {
        return $this->hasMany(HarvestRecord::class);
    }

    public function alertThresholds(): HasMany
    {
        return $this->hasMany(AlertThreshold::class);
    }

    public function iotDevices(): HasMany
    {
        return $this->hasMany(IotDevice::class);
    }

    public function temperatures(): HasMany
    {
        return $this->hasMany(HiveTemperature::class);
    }

    public function humidities(): HasMany
    {
        return $this->hasMany(HiveHumidity::class);
    }

    public function carbondioxides(): HasMany
    {
        return $this->hasMany(HiveCarbondioxide::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(HiveWeight::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HivePhoto::class);
    }

    public function audio(): HasMany
    {
        return $this->hasMany(HiveAudio::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(HiveVideo::class);
    }

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

    public function scopeWithinBounds($query, float $minLat, float $maxLat, float $minLng, float $maxLng)
    {
        return $query->whereBetween('latitude', [$minLat, $maxLat])
                    ->whereBetween('longitude', [$minLng, $maxLng]);
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

    public function getHybridCodeAttribute(): string
    {
        return $this->hybrid_identifier ?: $this->hive_code ?: '—';
    }
}
