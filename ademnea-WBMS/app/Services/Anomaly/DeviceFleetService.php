<?php

namespace App\Services\Anomaly;

use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Services\IotDeviceHealthEvaluator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * REQ-F-IOT-14 / UC-IOT-09 (fleet half): live health of every active
 * device — read straight from iot_device_telemetry via
 * IotDeviceHealthEvaluator, so it's independent of whether an anomaly has
 * ever been recorded — plus the open device-issue incidents.
 */
class DeviceFleetService
{
    public const PER_PAGE = 25;
    public const HEALTH_STATUSES = ['online', 'warning', 'offline'];

    public function __construct(private readonly IotDeviceHealthEvaluator $healthEvaluator)
    {
    }

    /**
     * @param  array{health?: ?string, hardware_team_id?: ?int, assignment?: ?string, has_issues?: bool}  $filters
     * @param  array{path: string, query: array}  $paginatorOptions
     */
    public function overview(array $filters, int $page, array $paginatorOptions): array
    {
        $now = now();

        // Health is derived per device, so the whole active fleet is loaded
        // once to build the KPIs, the attention panels and the filtered table.
        // Fleets here are in the tens-to-hundreds.
        $devices = IotDevice::query()
            ->where('active_flag', true)
            ->with(['telemetry', 'hive.apiary', 'hardwareTeam'])
            ->withCount(['anomalies as open_issues_count' => fn ($q) => $q->open()->deviceIssues()])
            ->orderBy('device_code')
            ->get();

        $health = $devices->mapWithKeys(fn (IotDevice $device) => [$device->id => $this->healthEvaluator->evaluate($device, $now)]);
        $statusCounts = $health->countBy('status');

        $filtered = $devices
            ->when(in_array($filters['health'] ?? null, self::HEALTH_STATUSES, true), fn ($c) => $c->filter(fn ($d) => $health[$d->id]['status'] === $filters['health']))
            ->when(! empty($filters['hardware_team_id']), fn ($c) => $c->where('hardware_team_id', (int) $filters['hardware_team_id']))
            ->when(($filters['assignment'] ?? null) === 'assigned', fn ($c) => $c->whereNotNull('hive_id'))
            ->when(($filters['assignment'] ?? null) === 'unassigned', fn ($c) => $c->whereNull('hive_id'))
            ->when(! empty($filters['has_issues']), fn ($c) => $c->where('open_issues_count', '>', 0))
            ->values();

        return [
            'fleetGeneratedAt' => $now,
            'health' => $health,
            'kpis' => [
                'total' => $devices->count(),
                'online' => $statusCounts->get('online', 0),
                'warning' => $statusCounts->get('warning', 0),
                'offline' => $statusCounts->get('offline', 0),
                'open_issues' => (int) $devices->sum('open_issues_count'),
            ],
            'lowBatteryDevices' => $devices
                ->filter(fn ($d) => $health[$d->id]['is_low_battery'])
                ->sortBy(fn ($d) => $d->telemetry->battery_level)
                ->values(),
            'weakSignalDevices' => $devices
                ->filter(fn ($d) => $health[$d->id]['is_weak_signal'])
                ->sortBy(fn ($d) => $d->telemetry->signal_strength)
                ->values(),
            'notHeardFromDevices' => $devices
                ->filter(fn ($d) => $health[$d->id]['status'] === 'offline')
                ->sortByDesc(fn ($d) => $health[$d->id]['minutes_since_contact'] ?? PHP_INT_MAX)
                ->values(),
            'fleet' => new LengthAwarePaginator(
                $filtered->forPage($page, self::PER_PAGE)->values(),
                $filtered->count(),
                self::PER_PAGE,
                $page,
                $paginatorOptions,
            ),
            'latestIssues' => SensorAnomaly::query()
                ->open()
                ->deviceIssues()
                ->with('device')
                ->orderByRaw('COALESCE(last_seen_at, detected_at) DESC')
                ->limit(8)
                ->get(),
            'hardwareTeams' => IotHardwareTeam::orderBy('name')->get(['id', 'name']),
        ];
    }
}
