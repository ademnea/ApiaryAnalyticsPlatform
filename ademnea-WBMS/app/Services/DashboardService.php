<?php

namespace App\Services;

use App\Models\AlertThreshold;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveCarbondioxide;
use App\Models\HiveHumidity;
use App\Models\HiveStatusHistory;
use App\Models\HiveTemperature;
use App\Models\HiveWeight;
use App\Models\Inspection;
use App\Models\IotDevice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * DashboardService
 *
 * Central service for all dashboard data aggregation.
 * The controller stays thin; all queries and business logic live here.
 *
 * Sections:
 *  1. Summary Counts        – REQ-DASH-01
 *  2. Hive Monitoring       – REQ-DASH-02
 *  3. Chart Data            – REQ-DASH-03
 *  4. Alerts                – REQ-DASH-04
 *  5. Recent Activity       – REQ-DASH-05
 */
class DashboardService
{
    // =========================================================================
    // 1. SUMMARY COUNTS
    // =========================================================================

    /**
     * Return all top-level summary counts for the overview cards.
     *
     * @return array<string, int|string>
     */
    public function getSummaryCounts(): array
    {
        return [
            'total_farmers'      => Farmer::count(),
            'active_farmers'     => Farmer::where('status', 'Active')->count(),
            'pending_farmers'    => Farmer::where('profile_status', 'pending')->count(),

            'total_apiaries'     => Apiary::count(),
            'active_apiaries'    => Apiary::where('status', 'Active')->count(),

            'total_hives'        => Hive::count(),
            'active_hives'       => Hive::where('current_status', 'Active')->count(),
            'inactive_hives'     => Hive::whereNotIn('current_status', ['Active'])->count(),

            // Team members: admin / field_officer / researcher roles (not farmers)
            'total_team_members' => User::whereIn('role', ['admin', 'field_officer', 'researcher'])
                                        ->where('status', 'active')
                                        ->count(),

            'total_iot_devices'  => IotDevice::count(),

            // Total registered users (all roles)
            'total_users'        => User::count(),
        ];
    }

    // =========================================================================
    // 2. HIVE MONITORING SUMMARY
    // =========================================================================

    /**
     * Return real-time / latest aggregate sensor readings and device status.
     * Sensor averages are windowed to the last 24 hours (a "current state"
     * figure, not an all-time average) and exclude readings the IoT
     * Condition Monitoring rules engine has flagged as suspect — a stuck or
     * physically-implausible reading would otherwise skew the average.
     *
     * @return array<string, mixed>
     */
    public function getHiveMonitoringSummary(): array
    {
        $since = Carbon::now()->subDay();

        $avgTemperature = HiveTemperature::where('suspect', false)
            ->where('recorded_at', '>=', $since)
            ->whereNotNull('brood_section')
            ->avg('brood_section');

        $avgHumidity = HiveHumidity::where('suspect', false)
            ->where('recorded_at', '>=', $since)
            ->whereNotNull('brood_section')
            ->avg('brood_section');

        $avgCo2 = HiveCarbondioxide::where('suspect', false)
            ->where('recorded_at', '>=', $since)
            ->avg('co2_level');

        $avgWeight = HiveWeight::where('suspect', false)
            ->where('recorded_at', '>=', $since)
            ->avg('weight_kg');

        $deviceCounts = $this->getDeviceHealthCounts();

        return [
            'avg_temperature'    => $avgTemperature !== null ? round($avgTemperature, 1) : null,
            'avg_humidity'       => $avgHumidity !== null ? round($avgHumidity, 1) : null,
            'avg_co2'            => $avgCo2 !== null ? round($avgCo2, 0) : null,
            'avg_weight'         => $avgWeight !== null ? round($avgWeight, 1) : null,

            'low_battery_count'  => $deviceCounts['low_battery'],
            'active_devices'     => $deviceCounts['active'],
            'offline_devices'    => $deviceCounts['offline'],

            // Hive status breakdown from existing hives table
            'hive_status_breakdown' => $this->getHiveStatusBreakdown(),
        ];
    }

    /**
     * Count hives grouped by their current_status value.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getHiveStatusBreakdown(): \Illuminate\Support\Collection
    {
        return Hive::select('current_status', DB::raw('count(*) as total'))
                   ->groupBy('current_status')
                   ->orderByDesc('total')
                   ->get();
    }

    /**
     * Active/offline/low-battery device counts, using the same
     * silence-threshold definition of "offline" as the IoT Condition
     * Device Fleet page (DeviceFleetController), so the two
     * dashboards never disagree about what "offline" means. Revoked
     * devices (active_flag = false) are excluded — they're intentionally
     * decommissioned, not failing.
     *
     * @return array{active: int, offline: int, low_battery: int}
     */
    private function getDeviceHealthCounts(): array
    {
        $silenceThresholdMinutes = (int) AlertThreshold::get('device_offline_silence_minutes', 120);
        $lowBatteryPct = (float) AlertThreshold::get('low_battery_pct', 20);
        $recentContactSince = Carbon::now()->subMinutes($silenceThresholdMinutes);

        $activeDevicesQuery = IotDevice::where('active_flag', true);
        $total = $activeDevicesQuery->count();

        $offline = IotDevice::where('active_flag', true)
            ->whereDoesntHave('telemetry', function ($query) use ($recentContactSince) {
                $query->where('last_heartbeat_at', '>=', $recentContactSince)
                    ->orWhere('last_data_received_at', '>=', $recentContactSince);
            })
            ->count();

        $lowBattery = IotDevice::where('active_flag', true)
            ->whereHas('telemetry', function ($query) use ($lowBatteryPct) {
                $query->whereNotNull('battery_level')->where('battery_level', '<=', $lowBatteryPct);
            })
            ->count();

        return [
            'active' => $total - $offline,
            'offline' => $offline,
            'low_battery' => $lowBattery,
        ];
    }

    // =========================================================================
    // 3. CHART DATA
    // =========================================================================

    /**
     * Return chart-ready data for the last N days.
     * Labels are ISO date strings; sensor series are daily averages of
     * non-suspect readings, null for days with no data.
     *
     * @param  int  $days  Number of past days to cover (default: 7)
     * @return array<string, mixed>
     */
    public function getChartData(int $days = 7): array
    {
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = Carbon::today()->subDays($i)->format('M d');
        }

        return [
            'labels' => $labels,
            'temperature' => $this->getDailyAverages(HiveTemperature::class, 'brood_section', $days),
            'humidity'    => $this->getDailyAverages(HiveHumidity::class, 'brood_section', $days),
            'co2'         => $this->getDailyAverages(HiveCarbondioxide::class, 'co2_level', $days),
            'weight'      => $this->getDailyAverages(HiveWeight::class, 'weight_kg', $days),

            // Hive activity: count of status-change events per day (real data)
            'hive_activity' => $this->getHiveActivityByDay($days, $labels),
        ];
    }

    /**
     * Daily average of a sensor column over the last N days, excluding
     * suspect readings. Returns one value per day (null where no readings
     * exist that day) in oldest-to-newest order, matching $labels.
     *
     * @param  class-string  $model
     * @return array<int, float|null>
     */
    private function getDailyAverages(string $model, string $column, int $days): array
    {
        $since = Carbon::today()->subDays($days - 1)->startOfDay();

        $raw = $model::selectRaw("DATE(recorded_at) as day, AVG({$column}) as avg")
            ->where('suspect', false)
            ->where('recorded_at', '>=', $since)
            ->whereNotNull($column)
            ->groupBy('day')
            ->pluck('avg', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $result[] = isset($raw[$date]) ? round((float) $raw[$date], 1) : null;
        }

        return $result;
    }

    /**
     * Count hive status-change events per day for the chart.
     *
     * @param  int    $days
     * @param  array  $labels
     * @return array<int, int>
     */
    private function getHiveActivityByDay(int $days, array $labels): array
    {
        $since = Carbon::today()->subDays($days - 1)->startOfDay();

        $raw = HiveStatusHistory::selectRaw("DATE(created_at) as day, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->pluck('total', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = Carbon::today()->subDays($i)->format('Y-m-d');
            $result[] = (int) ($raw[$date] ?? 0);
        }

        return $result;
    }

    // =========================================================================
    // 4. ALERTS
    // =========================================================================

    /**
     * Return structured alert lists for the dashboard alerts section.
     *
     * @return array<string, mixed>
     */
    public function getAlerts(): array
    {
        $silenceThresholdMinutes = (int) AlertThreshold::get('device_offline_silence_minutes', 120);
        $recentContactSince = Carbon::now()->subMinutes($silenceThresholdMinutes);

        return [
            // Hives that have been flagged as needing inspection (>30 days since last)
            'hives_needing_inspection' => Hive::with('apiary')
                ->where(function ($q) {
                    $q->whereNull('last_inspection_date')
                      ->orWhere('last_inspection_date', '<', Carbon::today()->subDays(30));
                })
                ->where('current_status', 'Active')
                ->orderBy('last_inspection_date')
                ->limit(5)
                ->get(),

            // Hives in a non-healthy status
            'critical_hives' => Hive::with('apiary')
                ->whereIn('current_status', ['Queenless', 'Absconded', 'Under Inspection'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(),

            // Pending farmer approvals waiting for admin action
            'pending_farmers' => Farmer::where('profile_status', 'pending')
                ->orderBy('created_at')
                ->limit(5)
                ->get(),

            'offline_devices' => IotDevice::where('active_flag', true)
                ->whereDoesntHave('telemetry', function ($query) use ($recentContactSince) {
                    $query->where('last_heartbeat_at', '>=', $recentContactSince)
                        ->orWhere('last_data_received_at', '>=', $recentContactSince);
                })
                ->with('hive')
                ->limit(5)
                ->get(),

            // No beekeeping-domain-confirmed "concerning but plausible" high-temperature
            // threshold exists yet (SRS §2.7.2c: thresholds must be confirmed with
            // domain experts before use). The Layer 1 rules engine's temp_max_c is a
            // physically-implausible/broken-sensor bound, not an environmental-risk one
            // — reusing it here would misrepresent "no anomaly" as "no risk". Left empty
            // deliberately rather than guessing a number.
            'high_temperature_alerts' => collect(),

            // Same rationale as high_temperature_alerts above — no confirmed threshold.
            'low_humidity_alerts' => collect(),
        ];
    }

    // =========================================================================
    // 5. RECENT ACTIVITY
    // =========================================================================

    /**
     * Return the most recent records across all key modules.
     *
     * @return array<string, mixed>
     */
    public function getRecentActivity(): array
    {
        return [
            // Most recently registered farmers
            'recent_farmers' => Farmer::orderByDesc('created_at')
                ->limit(5)
                ->get(),

            // Most recently registered apiaries (farms)
            'recent_apiaries' => Apiary::orderByDesc('created_at')
                ->limit(5)
                ->get(),

            // Most recently registered hives
            'recent_hives' => Hive::with('apiary')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),

            // Most recent hive status changes (acts as a proxy for system activity)
            'recent_status_changes' => HiveStatusHistory::with(['hive', 'changedBy'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),

            'recent_sensor_readings' => $this->getRecentSensorReadings(),

            'recent_inspections' => Inspection::with(['hive', 'inspector'])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Merge the 5 most recent readings across all four sensor tables into
     * one feed, newest first. Each entry carries a pre-formatted display
     * value and the `suspect` flag the Layer 1 rules engine set at
     * ingestion, so the view can flag readings it shouldn't be trusted.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function getRecentSensorReadings(int $limit = 5): \Illuminate\Support\Collection
    {
        $temperature = HiveTemperature::with(['hive.apiary'])
            ->latest('recorded_at')->limit($limit)->get()
            ->map(fn ($r) => [
                'hive' => $r->hive,
                'sensor_type' => 'Temperature',
                'icon' => 'bi-thermometer-half',
                'value' => $this->formatZoneValue($r, '°C'),
                'suspect' => $r->suspect,
                'recorded_at' => $r->recorded_at,
            ]);

        $humidity = HiveHumidity::with(['hive.apiary'])
            ->latest('recorded_at')->limit($limit)->get()
            ->map(fn ($r) => [
                'hive' => $r->hive,
                'sensor_type' => 'Humidity',
                'icon' => 'bi-droplet-half',
                'value' => $this->formatZoneValue($r, '%'),
                'suspect' => $r->suspect,
                'recorded_at' => $r->recorded_at,
            ]);

        $co2 = HiveCarbondioxide::with(['hive.apiary'])
            ->latest('recorded_at')->limit($limit)->get()
            ->map(fn ($r) => [
                'hive' => $r->hive,
                'sensor_type' => 'CO₂',
                'icon' => 'bi-wind',
                'value' => $r->co2_level !== null ? number_format($r->co2_level, 0).' ppm' : '—',
                'suspect' => $r->suspect,
                'recorded_at' => $r->recorded_at,
            ]);

        $weight = HiveWeight::with(['hive.apiary'])
            ->latest('recorded_at')->limit($limit)->get()
            ->map(fn ($r) => [
                'hive' => $r->hive,
                'sensor_type' => 'Weight',
                'icon' => 'bi-speedometer',
                'value' => $r->weight_kg !== null ? number_format($r->weight_kg, 1).' kg' : '—',
                'suspect' => $r->suspect,
                'recorded_at' => $r->recorded_at,
            ]);

        return $temperature->concat($humidity)->concat($co2)->concat($weight)
            ->sortByDesc('recorded_at')
            ->take($limit)
            ->values();
    }

    /**
     * Format a three-zone reading (brood_section preferred as the single
     * most clinically meaningful figure, falling back to honey_section
     * then exterior), noting which zone is shown.
     */
    private function formatZoneValue(HiveTemperature|HiveHumidity $reading, string $unit): string
    {
        $zones = ['brood_section' => 'brood', 'honey_section' => 'honey', 'exterior' => 'ext'];

        foreach ($zones as $column => $label) {
            if ($reading->{$column} !== null) {
                return number_format($reading->{$column}, 1).$unit." ({$label})";
            }
        }

        return '—';
    }
}
