<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

/**
 * DashboardController
 *
 * Thin controller — delegates all data aggregation to DashboardService.
 * Responsibility: receive the request, call the service, return the view.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard
    ) {}

    /**
     * Render the admin dashboard.
     *
     * Passes five named view variables so partials stay decoupled:
     *   $summary    – overview stat counts
     *   $monitoring – hive/sensor aggregate summary
     *   $chartData  – Chart.js-ready arrays (labels + series)
     *   $alerts     – alert collections by category
     *   $activity   – recent records across modules
     */
    public function index(Request $request): \Illuminate\View\View
    {
        return view('admin.dashboard', [
            'summary'    => $this->dashboard->getSummaryCounts(),
            'monitoring' => $this->dashboard->getHiveMonitoringSummary(),
            'chartData'  => $this->dashboard->getChartData(days: 7),
            'alerts'     => $this->dashboard->getAlerts(),
            'activity'   => $this->dashboard->getRecentActivity(),
        ]);
    }
}
