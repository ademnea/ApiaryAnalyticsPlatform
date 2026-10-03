<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Anomaly\AnomalyDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SRS UC-IOT-11: per-hive anomaly heatmap / trend analytics over a
 * configurable window.
 */
class AnomalyAnalyticsController extends Controller
{
    public function __construct(private readonly AnomalyDashboardService $dashboard)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.anomaly.analytics', $this->dashboard->heatmap((int) $request->query('days', 7)));
    }
}
