<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Anomaly\SystemAlertService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SRS REQ-F-IOT-17 (alert routing), admin side: System Alerts page.
 */
class SystemAlertController extends Controller
{
    public function __construct(private readonly SystemAlertService $alerts)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->alerts->filtersFrom($request->query());

        return view('admin.alerts.index', [
            'alerts' => $this->alerts->paginate($filters)->withQueryString(),
            'filters' => $filters,
            'kpis' => $this->alerts->kpis(),
            'hives' => $this->alerts->hiveOptions(),
            'types' => SystemAlertService::TYPES,
        ]);
    }
}
