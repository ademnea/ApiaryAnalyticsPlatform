<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An anomaly incident. There is at most one open (unresolved) row per
 * (device, hive, sensor_type, anomaly_type): repeat detections touch that
 * row via recordOrTouch() instead of inserting a new one. Once resolved
 * (automatically on recovery, or manually by an admin) the row is closed
 * for good and a recurrence opens a new incident.
 *
 * Two categories share this table:
 *  - device issues  (sensor_type = 'telemetry'): battery, signal, offline…
 *  - hive conditions (every other sensor_type): threshold breaches, frozen
 *    sensors, statistical deviations.
 */
class SensorAnomaly extends Model
{
    protected $table = 'sensor_anomalies';

    // No updated_at column — the lifecycle columns carry their own timestamps.
    public $timestamps = false;

    public const DEVICE_SENSOR_TYPE = 'telemetry';

    protected $fillable = [
        'device_id', 'hive_id', 'sensor_type', 'anomaly_type', 'anomaly_score',
        'record_value', 'last_record_value', 'detection_layer',
        'detected_at', 'last_seen_at', 'occurrences',
        'alerted', 'alerted_at', 'acknowledged_at', 'acknowledged_by',
        'resolved', 'resolved_at', 'resolved_by', 'resolution_note', 'auto_resolved',
        'created_at',
    ];

    protected $casts = [
        'record_value' => 'array',
        'last_record_value' => 'array',
        'alerted' => 'boolean',
        'resolved' => 'boolean',
        'auto_resolved' => 'boolean',
        'anomaly_score' => 'float',
        'occurrences' => 'integer',
        'detected_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'alerted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class, 'hive_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'source_anomaly_id');
    }

    // ---- Scopes ----

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('resolved', false);
    }

    public function scopeDeviceIssues(Builder $query): Builder
    {
        return $query->where('sensor_type', self::DEVICE_SENSOR_TYPE);
    }

    public function scopeHiveConditions(Builder $query): Builder
    {
        return $query->where('sensor_type', '!=', self::DEVICE_SENSOR_TYPE);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'unresolved' => $query->where('resolved', false),
            'open' => $query->where('resolved', false)->whereNull('acknowledged_at'),
            'acknowledged' => $query->where('resolved', false)->whereNotNull('acknowledged_at'),
            'resolved' => $query->where('resolved', true),
            default => $query,
        };
    }

    public function scopeWithSeverity(Builder $query, string $severity): Builder
    {
        return match ($severity) {
            'critical' => $query->whereIn('anomaly_type', self::SEVERITY_CRITICAL),
            'warning' => $query->whereIn('anomaly_type', self::SEVERITY_WARNING),
            'info' => $query->whereNotIn('anomaly_type', [...self::SEVERITY_CRITICAL, ...self::SEVERITY_WARNING]),
            default => $query,
        };
    }

    // ---- Incident recording ----

    /**
     * Opens a new incident, or touches the matching open one (bumping
     * occurrences/last_seen_at/last_record_value). Callers check
     * $anomaly->wasRecentlyCreated to decide whether to alert.
     */
    public static function recordOrTouch(array $attributes): self
    {
        $open = static::query()
            ->open()
            ->where('device_id', $attributes['device_id'])
            ->where('hive_id', $attributes['hive_id'] ?? null)
            ->where('sensor_type', $attributes['sensor_type'])
            ->where('anomaly_type', $attributes['anomaly_type'])
            ->latest('id')
            ->first();

        if ($open) {
            $open->forceFill([
                'occurrences' => $open->occurrences + 1,
                'last_seen_at' => $attributes['detected_at'] ?? now(),
                'last_record_value' => $attributes['record_value'] ?? null,
                'anomaly_score' => $attributes['anomaly_score'] ?? $open->anomaly_score,
            ])->save();

            return $open;
        }

        return static::create($attributes + [
            'last_seen_at' => $attributes['detected_at'] ?? now(),
            'occurrences' => 1,
        ]);
    }

    /**
     * Closes open incidents of the given types for a device/sensor because
     * the condition is no longer observed. Returns the number resolved.
     *
     * @param  array<int, string>  $anomalyTypes
     */
    public static function autoResolve(int $deviceId, string $sensorType, array $anomalyTypes, ?int $hiveId = null): int
    {
        if ($anomalyTypes === []) {
            return 0;
        }

        return static::query()
            ->open()
            ->where('device_id', $deviceId)
            ->where('sensor_type', $sensorType)
            ->whereIn('anomaly_type', $anomalyTypes)
            ->when($hiveId !== null, fn ($q) => $q->where('hive_id', $hiveId))
            ->update([
                'resolved' => true,
                'resolved_at' => now(),
                'auto_resolved' => true,
            ]);
    }

    public function acknowledge(User $by): void
    {
        if ($this->resolved || $this->acknowledged_at) {
            return;
        }

        $this->forceFill(['acknowledged_at' => now(), 'acknowledged_by' => $by->id])->save();
    }

    public function resolve(?User $by, ?string $note = null): void
    {
        if ($this->resolved) {
            return;
        }

        $this->forceFill([
            'resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $by?->id,
            'resolution_note' => $note,
            'auto_resolved' => $by === null,
        ])->save();
    }

    // ---- Presentation ----

    public function status(): string
    {
        return match (true) {
            $this->resolved => 'resolved',
            $this->acknowledged_at !== null => 'acknowledged',
            default => 'open',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status()) {
            'resolved' => 'badge-active',
            'acknowledged' => 'badge-info',
            default => 'badge-warning',
        };
    }

    public function isDeviceIssue(): bool
    {
        return $this->sensor_type === self::DEVICE_SENSOR_TYPE;
    }

    public function category(): string
    {
        return $this->isDeviceIssue() ? 'device' : 'hive';
    }

    public const SEVERITY_CRITICAL = ['critical_battery', 'device_offline', 'reboot_loop', 'storage_full'];
    public const SEVERITY_WARNING = ['low_battery', 'weak_signal', 'static_threshold_breach', 'frozen_sensor', 'submission_delay'];

    /** Badge/icon tier for this anomaly_type — used consistently across the dashboard, analytics, and device-detail views. */
    public function severity(): string
    {
        return match (true) {
            in_array($this->anomaly_type, self::SEVERITY_CRITICAL, true) => 'critical',
            in_array($this->anomaly_type, self::SEVERITY_WARNING, true) => 'warning',
            default => 'info', // statistical_deviation, ml_* — statistically notable, not yet a hard rule breach
        };
    }

    public function badgeClass(): string
    {
        return match ($this->severity()) {
            'critical' => 'badge-offline',
            'warning' => 'badge-warning',
            default => 'badge-info',
        };
    }

    public function icon(): string
    {
        return match ($this->anomaly_type) {
            'static_threshold_breach' => 'bi-thermometer-high',
            'frozen_sensor' => 'bi-snow',
            'statistical_deviation' => 'bi-graph-up-arrow',
            'low_battery' => 'bi-battery-half',
            'critical_battery' => 'bi-battery',
            'weak_signal' => 'bi-reception-1',
            'reboot_loop' => 'bi-arrow-repeat',
            'storage_full' => 'bi-hdd-fill',
            'device_offline' => 'bi-wifi-off',
            'submission_delay' => 'bi-clock-history',
            default => 'bi-shield-exclamation',
        };
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->anomaly_type));
    }

    /** "brood_section=75, mean=34.2" — compact rendering of a record_value array. */
    public static function formatValues(?array $values): string
    {
        if (! $values) {
            return '—';
        }

        return collect($values)
            ->map(fn ($v, $k) => ucwords(str_replace('_', ' ', $k)) . ': ' . (is_float($v) ? round($v, 2) : (is_scalar($v) ? $v : json_encode($v))))
            ->implode(', ');
    }
}
