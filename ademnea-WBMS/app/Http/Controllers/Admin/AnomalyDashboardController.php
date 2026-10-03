<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Anomaly\AnomalyDashboardService;
use Illuminate\View\View;

/**
 * SRS UC-IOT-09: hive-condition anomaly overview — threshold breaches,
 * frozen sensors and statistical deviations. Device health (battery,
 * signal, connectivity) lives on the Device Fleet page.
 */
class AnomalyDashboardController extends Controller
{
    public function __construct(private readonly AnomalyDashboardService $dashboard)
    {
    }

    public function index(): View
    {
        return view('admin.anomaly.dashboard', $this->dashboard->hiveConditionsSummary());
    }
}
