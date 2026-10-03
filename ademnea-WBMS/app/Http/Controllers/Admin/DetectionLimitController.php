<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDetectionLimitsRequest;
use App\Models\Hive;
use App\Services\Anomaly\DetectionLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The limits the condition-monitoring rules judge against, fleet-wide or
 * overridden for one hive. Anyone who can see the monitoring pages can read
 * them; changing them needs manage-hives, as on the Alert Thresholds page.
 */
class DetectionLimitController extends Controller
{
    public function __construct(private readonly DetectionLimitService $limits)
    {
    }

    public function index(Request $request): View
    {
        $hive = Hive::find((int) $request->query('hive_id'));

        return view('admin.anomaly.limits', [
            'hive' => $hive,
            'groups' => $this->limits->groups($hive),
            'hives' => Hive::orderBy('display_name')->get(['id', 'display_name', 'hive_code']),
            'hivesWithOverrides' => $this->limits->hivesWithOverrides(),
            'canEdit' => $request->user()->can('manage-hives'),
        ]);
    }

    public function update(UpdateDetectionLimitsRequest $request): RedirectResponse
    {
        $hive = $request->hive();

        $this->limits->save($hive, $request->validated('limits'));

        // Back to the tab the save came from.
        $tab = preg_match('/^tab-[a-z-]+$/', (string) $request->input('tab')) ? $request->input('tab') : null;

        $redirect = redirect()
            ->route('admin.anomaly.limits', array_filter(['hive_id' => $hive?->id]))
            ->with('success', $hive
                ? 'Limits saved for hive '.($hive->display_name ?? $hive->hive_code).'. They apply from the next reading.'
                : 'Limits saved for all hives. They apply from the next reading.');

        return $tab ? $redirect->withFragment($tab) : $redirect;
    }
}
