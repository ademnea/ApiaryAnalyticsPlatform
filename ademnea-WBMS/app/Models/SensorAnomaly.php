<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorAnomaly extends Model
{
    protected $table = 'sensor_anomalies';

    // Append-only: no updated_at column. alerted/resolved are the only
    // fields ever mutated after insert.
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'hive_id', 'sensor_type', 'anomaly_type', 'anomaly_score',
        'record_value', 'detection_layer', 'detected_at',
        'alerted', 'alerted_at', 'resolved', 'resolved_at', 'created_at',
    ];

    protected $casts = [
        'record_value' => 'array',
        'alerted' => 'boolean',
        'resolved' => 'boolean',
        'anomaly_score' => 'float',
        'detected_at' => 'datetime',
        'alerted_at' => 'datetime',
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

    private const SEVERITY_CRITICAL = ['critical_battery', 'device_offline', 'reboot_loop', 'storage_full'];
    private const SEVERITY_WARNING = ['low_battery', 'weak_signal', 'static_threshold_breach', 'frozen_sensor', 'submission_delay'];

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
}
