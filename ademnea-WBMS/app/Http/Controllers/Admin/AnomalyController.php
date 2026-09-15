<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AnomalyIncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Anomaly incidents: filterable list, detail view, and the
 * acknowledge/resolve lifecycle. Serves both categories — hive
 * conditions (Condition Monitoring) and device issues (Device Fleet).
 */
class AnomalyController extends Controller
{
    public function __construct(private readonly AnomalyIncidentService $incidents)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->incidents->filtersFrom($request->query());

        return view('admin.anomaly.index', [
            'anomalies' => $this->incidents->paginate($filters)->withQueryString(),
            'filters' => $filters,
            'categories' => AnomalyIncidentService::CATEGORIES,
            'statuses' => AnomalyIncidentService::STATUSES,
            'severities' => AnomalyIncidentService::SEVERITIES,
            ...$this->incidents->filterOptions($filters['category']),
        ]);
    }

    public function show(SensorAnomaly $anomaly): View
    {
        return view('admin.anomaly.show', [
            'anomaly' => $this->incidents->loadDetail($anomaly),
            'history' => $this->incidents->history($anomaly),
        ]);
    }

    public function acknowledge(Request $request, SensorAnomaly $anomaly): RedirectResponse
    {
        return $this->incidents->acknowledge($anomaly, $request->user())
            ? back()->with('success', 'Anomaly acknowledged.')
            : back()->with('warning', 'This anomaly is already resolved.');
    }

    public function resolve(Request $request, SensorAnomaly $anomaly): RedirectResponse
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return $this->incidents->resolve($anomaly, $request->user(), $validated['note'] ?? null)
            ? back()->with('success', 'Anomaly resolved. If the condition recurs, a new incident will be opened.')
            : back()->with('warning', 'This anomaly is already resolved.');
    }
}
