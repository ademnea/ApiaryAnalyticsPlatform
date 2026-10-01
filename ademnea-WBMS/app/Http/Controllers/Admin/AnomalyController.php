<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AlertRouting;
use App\Services\Anomaly\AnomalyEvidenceService;
use App\Services\Anomaly\AnomalyIncidentService;
use App\Services\Anomaly\DetectionLimitService;
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
    public function __construct(
        private readonly AnomalyIncidentService $incidents,
        private readonly AnomalyEvidenceService $evidence,
        private readonly DetectionLimitService $limits,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $this->incidents->filtersFrom($request->query());

        $anomalies = $this->incidents->paginate($filters)->withQueryString();

        return view('admin.anomaly.index', [
            'anomalies' => $anomalies,
            'headlines' => $anomalies->getCollection()->mapWithKeys(fn (SensorAnomaly $anomaly) => [$anomaly->id => $this->evidence->headline($anomaly)]),
            'filters' => $filters,
            'categories' => AnomalyIncidentService::CATEGORIES,
            'statuses' => AnomalyIncidentService::STATUSES,
            'severities' => AnomalyIncidentService::SEVERITIES,
            ...$this->incidents->filterOptions($filters['category']),
        ]);
    }

    public function show(SensorAnomaly $anomaly): View
    {
        $anomaly = $this->incidents->loadDetail($anomaly);

        return view('admin.anomaly.show', [
            'anomaly' => $anomaly,
            'history' => $this->incidents->history($anomaly),
            'explanation' => $this->evidence->explain($anomaly),
            'recommendedAction' => AlertRouting::recommendedAction($anomaly->anomaly_type),
            'limitRule' => $this->limits->ruleFor($anomaly->anomaly_type),
            'chart' => $this->evidence->chart($anomaly),
            'notificationSummary' => $this->evidence->notificationSummary($anomaly),
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
