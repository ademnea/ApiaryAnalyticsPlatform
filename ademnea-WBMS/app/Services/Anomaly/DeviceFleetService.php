<?php

namespace App\Services\Anomaly;

use App\Models\AlertThreshold;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Services\IotDeviceHealthEvaluator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * REQ-F-IOT-14 / UC-IOT-09 (fleet half): unified health, battery, signal,
 * and open-issue monitoring for every active device.
 */
class DeviceFleetService
{
    public const PER_PAGE = 25;

    public const HEALTH_STATUSES = ['online', 'warning', 'offline'];

    public function __construct(private readonly IotDeviceHealthEvaluator $healthEvaluator) {}

    /**
     * @param  array{health?: ?string, hardware_team_id?: ?int, assignment?: ?string, has_issues?: bool, battery?: ?string}  $filters
     * @param  array{path: string, query: array}  $paginatorOptions
     */
    public function overview(array $filters, int $page, array $paginatorOptions): array
    {
        $now = now(config('app.local_timezone'));

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
        $batteryReadingStaleAfterMinutes = (int) AlertThreshold::get('device_offline_silence_minutes', 120);
        $batteryReadings = $devices->mapWithKeys(function (IotDevice $device) use ($batteryReadingStaleAfterMinutes, $now): array {
            $telemetry = $device->telemetry;
            $hasBatteryReading = $telemetry && collect([
                $telemetry->battery_level,
                $telemetry->battery_voltage,
                $telemetry->battery_current,
            ])->contains(fn ($value) => $value !== null);
            $recordedAt = $hasBatteryReading
                ? ($telemetry->battery_reading_at ?? $telemetry->last_heartbeat_at)
                : null;

            return [$device->id => [
                'recorded_at' => $recordedAt,
                'is_stale' => $recordedAt !== null
                    && $recordedAt->diffInMinutes($now) > $batteryReadingStaleAfterMinutes,
            ]];
        });

        $filtered = $devices
            ->when(in_array($filters['health'] ?? null, self::HEALTH_STATUSES, true), fn ($c) => $c->filter(fn ($d) => $health[$d->id]['status'] === $filters['health']))
            ->when(! empty($filters['hardware_team_id']), fn ($c) => $c->where('hardware_team_id', (int) $filters['hardware_team_id']))
            ->when(($filters['assignment'] ?? null) === 'assigned', fn ($c) => $c->whereNotNull('hive_id'))
            ->when(($filters['assignment'] ?? null) === 'unassigned', fn ($c) => $c->whereNull('hive_id'))
            ->when(! empty($filters['has_issues']), fn ($c) => $c->where('open_issues_count', '>', 0))
            ->when(($filters['battery'] ?? null) === 'low', fn ($c) => $c->filter(fn ($d) => $health[$d->id]['is_low_battery']))
            ->when(($filters['battery'] ?? null) === 'stale', fn ($c) => $c->filter(fn ($d) => $batteryReadings[$d->id]['is_stale']))
            ->values();

        $batteryTrendStart = $now->copy()->startOfDay()->subDays(6);
        $localOffset = $now->format('P');
        $localReadingDay = "DATE(CONVERT_TZ(iot_device_telemetry_history.recorded_at, '+00:00', '{$localOffset}'))";
        $batteryTrend = $devices->isEmpty()
            ? collect()
            : IotDeviceTelemetryHistory::query()
                ->join('iot_devices', 'iot_devices.id', '=', 'iot_device_telemetry_history.device_id')
                ->whereIn('iot_devices.id', $devices->modelKeys())
                ->where('iot_devices.active_flag', true)
                ->where('iot_device_telemetry_history.recorded_at', '>=', $batteryTrendStart->copy()->utc())
                ->whereNotNull('iot_device_telemetry_history.battery_level')
                ->selectRaw("{$localReadingDay} as reading_day")
                ->selectRaw('AVG(iot_device_telemetry_history.battery_level) as average_battery_level')
                ->selectRaw('AVG(iot_device_telemetry_history.battery_voltage) as average_battery_voltage')
                ->selectRaw('COUNT(*) as readings_count')
                ->groupBy('reading_day')
                ->orderBy('reading_day')
                ->get();
        $trendByDay = $batteryTrend->keyBy('reading_day');
        $trendDays = collect(range(0, 6))->map(fn (int $daysAgo) => $batteryTrendStart->copy()->addDays($daysAgo));
        $populatedBatteryDays = $batteryTrend->filter(fn ($day) => $day->average_battery_level !== null)->values();
        $firstBatteryDay = $populatedBatteryDays->first();
        $lastBatteryDay = $populatedBatteryDays->last();
        $batteryTrendChange = $firstBatteryDay && $lastBatteryDay && $firstBatteryDay !== $lastBatteryDay
            ? (float) $lastBatteryDay->average_battery_level - (float) $firstBatteryDay->average_battery_level
            : null;
        $batteryReportingDevices = $devices->filter(fn ($device) => $device->telemetry?->battery_level !== null
            || $device->telemetry?->battery_voltage !== null
            || $device->telemetry?->battery_current !== null);
        $batteryLevels = $devices->map(fn ($device) => $device->telemetry?->battery_level)->filter(fn ($value) => $value !== null);
        $batteryVoltages = $devices->map(fn ($device) => $device->telemetry?->battery_voltage)->filter(fn ($value) => $value !== null);
        $onlineRate = $devices->isEmpty() ? 0 : round(($statusCounts->get('online', 0) / $devices->count()) * 100, 1);

        return [
            'fleetGeneratedAt' => $now,
            'health' => $health,
            'batteryReadings' => $batteryReadings,
            'batteryReadingStaleAfterMinutes' => $batteryReadingStaleAfterMinutes,
            'batteryTrendDays' => $batteryTrend->count(),
            'batteryTrendReadingsCount' => $batteryTrend->sum('readings_count'),
            'batteryTrendChange' => $batteryTrendChange,
            'batteryTrendFirstDay' => $firstBatteryDay?->reading_day,
            'batteryTrendLastDay' => $lastBatteryDay?->reading_day,
            'batteryTrendChart' => [
                'labels' => $trendDays->map(fn ($day) => $day->format('M j'))->values(),
                'battery' => $trendDays->map(fn ($day) => $trendByDay->get($day->toDateString())?->average_battery_level)->values(),
                'voltage' => $trendDays->map(fn ($day) => $trendByDay->get($day->toDateString())?->average_battery_voltage)->values(),
            ],
            'kpis' => [
                'total' => $devices->count(),
                'online' => $statusCounts->get('online', 0),
                'warning' => $statusCounts->get('warning', 0),
                'offline' => $statusCounts->get('offline', 0),
                'online_rate' => $onlineRate,
                'battery_reporting' => $batteryReportingDevices->count(),
                'average_battery_level' => $batteryLevels->isEmpty() ? null : $batteryLevels->avg(),
                'average_voltage' => $batteryVoltages->isEmpty() ? null : $batteryVoltages->avg(),
                'low_battery' => $health->filter(fn (array $status) => $status['is_low_battery'])->count(),
                'stale_battery' => $batteryReadings->filter(fn (array $reading) => $reading['is_stale'])->count(),
                'open_issues' => (int) $devices->sum('open_issues_count'),
            ],
            'lowBatteryDevices' => $devices
                ->filter(fn ($d) => $health[$d->id]['is_low_battery'])
                ->sortBy(fn ($d) => $d->telemetry->battery_level)
                ->values(),
            'staleBatteryDevices' => $devices
                ->filter(fn ($device) => $batteryReadings[$device->id]['is_stale'])
                ->sortBy(fn ($device) => $batteryReadings[$device->id]['recorded_at'])
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
