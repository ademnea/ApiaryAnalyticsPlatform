<?php

namespace App\Services;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveStatusHistory;
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
            'active_farmers'     => Farmer::where('is_active', true)->count(),
            'pending_farmers'    => Farmer::where('profile_status', 'pending')->count(),

            'total_apiaries'     => Apiary::count(),
            'active_apiaries'    => Apiary::where('is_active', true)->count(),

            'total_hives'        => Hive::count(),
            'active_hives'       => Hive::where('status', 'active')->count(),
            'inactive_hives'     => Hive::whereNotIn('status', ['active'])->count(),

            // Team members: admin / field_officer / researcher roles (not farmers)
            'total_team_members' => User::whereIn('role', ['admin', 'field_officer', 'researcher'])
                                        ->where('status', 'active')
                                        ->count(),

            // TODO: Replace with IotDevice::count() once the iot_devices table and model exist.
            'total_iot_devices'  => null,

            // Total registered users (all roles)
            'total_users'        => User::count(),
        ];
    }

    // =========================================================================
    // 2. HIVE MONITORING SUMMARY
    // =========================================================================

    /**
     * Return real-time / latest aggregate sensor readings and device status.
     * All IoT / sensor fields are placeholders until the sensor modules are built.
     *
     * @return array<string, mixed>
     */
    public function getHiveMonitoringSummary(): array
    {
        return [
            // TODO: Replace with actual aggregates from hive_temperatures table
            //       e.g. HiveTemperature::avg('value')
            'avg_temperature'    => null,

            // TODO: Replace with actual aggregates from hive_humidity table
            'avg_humidity'       => null,

            // TODO: Replace with actual aggregates from hive_co2 table
            'avg_co2'            => null,

            // TODO: Replace with actual aggregates from hive_weights table
            'avg_weight'         => null,

            // TODO: Replace with IotDevice::where('battery_status', 'low')->count()
            'low_battery_count'  => null,

            // TODO: Replace with IotDevice::where('status', 'online')->count()
            'active_devices'     => null,

            // TODO: Replace with IotDevice::where('status', 'offline')->count()
            'offline_devices'    => null,

            // Hive status breakdown from existing hives table
            'hive_status_breakdown' => $this->getHiveStatusBreakdown(),
        ];
    }

    /**
     * Count hives grouped by their status value.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getHiveStatusBreakdown(): \Illuminate\Support\Collection
    {
        return Hive::select('status', DB::raw('count(*) as total'))
                   ->groupBy('status')
                   ->orderByDesc('total')
                   ->get();
    }

    // =========================================================================
    // 3. CHART DATA
    // =========================================================================

    /**
     * Return chart-ready data for the last N days.
     * Labels are ISO date strings; all sensor series are placeholders.
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

            // TODO: Replace with real daily averages from hive_temperatures
            //       e.g. HiveTemperature::selectRaw('DATE(created_at) as day, AVG(value) as avg')
            //               ->groupBy('day')->orderBy('day')->pluck('avg', 'day')->values()
            'temperature' => array_fill(0, $days, null),

            // TODO: Replace with real daily averages from hive_humidity
            'humidity'    => array_fill(0, $days, null),

            // TODO: Replace with real daily averages from hive_co2
            'co2'         => array_fill(0, $days, null),

            // TODO: Replace with real daily averages from hive_weights
            'weight'      => array_fill(0, $days, null),

            // Hive activity: count of status-change events per day (real data)
            'hive_activity' => $this->getHiveActivityByDay($days, $labels),
        ];
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
        return [
            // Hives that have been flagged as needing inspection (>30 days since last)
            'hives_needing_inspection' => Hive::with('apiary')
                ->where(function ($q) {
                    $q->whereNull('last_inspection_date')
                      ->orWhere('last_inspection_date', '<', Carbon::today()->subDays(30));
                })
                ->where('status', 'active')
                ->orderBy('last_inspection_date')
                ->limit(5)
                ->get(),

            // Hives in a non-healthy status
            'critical_hives' => Hive::with('apiary')
                ->whereIn('status', ['queenless', 'absconded', 'under_inspection'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(),

            // Pending farmer approvals waiting for admin action
            'pending_farmers' => Farmer::where('profile_status', 'pending')
                ->orderBy('created_at')
                ->limit(5)
                ->get(),

            // TODO: Add offline device alerts once IotDevice model exists
            //       e.g. 'offline_devices' => IotDevice::where('status','offline')->limit(5)->get()
            'offline_devices' => collect(),

            // TODO: Add high temperature alerts once HiveTemperature model exists
            'high_temperature_alerts' => collect(),

            // TODO: Add low humidity alerts once HiveHumidity model exists
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

            // TODO: Replace with real sensor readings once HiveTemperature model exists
            //       e.g. HiveTemperature::with('device.hive')->latest()->limit(5)->get()
            'recent_sensor_readings' => collect(),

            // TODO: Replace with real inspections once Inspection model exists
            //       e.g. Inspection::with(['hive','inspector'])->latest()->limit(5)->get()
            'recent_inspections' => collect(),
        ];
    }
}
