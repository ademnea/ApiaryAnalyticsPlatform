<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apiary;
use App\Models\Hive;
use App\Models\ScheduledReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduledReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.scheduled', [
            'reports' => ScheduledReport::query()->with(['apiary', 'hive'])->latest()->paginate(20),
            'apiaries' => Apiary::query()->with('farmer')->orderBy('name')->get(),
            'hives' => Hive::query()->with('apiary')->orderBy('hive_code')->get(),
            'timezone' => config('app.timezone'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'frequency' => ['required', Rule::in(['weekly', 'monthly', 'once'])],
            'day_of_week' => ['nullable', 'required_if:frequency,weekly', 'integer', 'between:1,7'],
            'day_of_month' => ['nullable', 'required_if:frequency,monthly', 'integer', 'between:1,31'],
            'schedule_time' => ['nullable', 'required_if:frequency,weekly,monthly', 'date_format:H:i'],
            'scheduled_at' => ['nullable', 'required_if:frequency,once', 'date_format:Y-m-d\TH:i', 'after:now'],
            'period_from' => ['nullable', 'required_if:frequency,once', 'date_format:Y-m-d'],
            'period_to' => ['nullable', 'required_if:frequency,once', 'date_format:Y-m-d', 'after_or_equal:period_from'],
            'scope_type' => ['required', Rule::in(['apiary', 'hive'])],
            'apiary_id' => ['nullable', 'required_if:scope_type,apiary', 'prohibited_if:scope_type,hive', Rule::exists('apiaries', 'id')->whereNull('deleted_at')],
            'hive_id' => ['nullable', 'required_if:scope_type,hive', 'prohibited_if:scope_type,apiary', Rule::exists('hives', 'id')->whereNull('deleted_at')],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => [
                Rule::in(['admins', 'farmers', 'hardware_teams']),
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($value === 'hardware_teams' && $request->input('scope_type') !== 'hive') {
                        $fail('Hardware-team recipients require a hive-scoped report.');
                    }
                },
            ],
        ]);

        $timezone = config('app.timezone');
        $scheduledAt = $data['frequency'] === 'once'
            ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], $timezone)
            : null;
        $report = new ScheduledReport([
            'name' => $data['name'],
            'frequency' => $data['frequency'],
            'day_of_week' => $data['frequency'] === 'weekly' ? (int) $data['day_of_week'] : null,
            'day_of_month' => $data['frequency'] === 'monthly' ? (int) $data['day_of_month'] : null,
            'schedule_time' => $data['frequency'] === 'once' ? null : $data['schedule_time'],
            'scheduled_at' => $scheduledAt,
            'period_from' => $data['period_from'] ?? null,
            'period_to' => $data['period_to'] ?? null,
            'timezone' => $timezone,
            'apiary_id' => $data['scope_type'] === 'apiary' ? (int) $data['apiary_id'] : null,
            'hive_id' => $data['scope_type'] === 'hive' ? (int) $data['hive_id'] : null,
            'recipients' => array_values($data['recipients']),
            'is_active' => true,
        ]);
        $report->next_run_at = $report->nextRunAfter(CarbonImmutable::now($timezone));
        $report->save();

        return to_route('admin.reports.scheduled.index')->with('success', 'Scheduled report created. Delivery uses the configured mail service.');
    }

    public function toggle(ScheduledReport $report): RedirectResponse
    {
        if ($report->frequency === 'once') {
            return back()->with('error', 'One-time reports cannot be resumed. Create a new schedule instead.');
        }

        $active = ! $report->is_active;
        $report->update([
            'is_active' => $active,
            'next_run_at' => $active ? $report->nextRunAfter(CarbonImmutable::now($report->timezone)) : null,
            'last_error' => null,
        ]);

        return to_route('admin.reports.scheduled.index')->with('success', $active ? 'Report schedule resumed.' : 'Report schedule paused.');
    }

    public function destroy(ScheduledReport $report): RedirectResponse
    {
        $report->delete();

        return to_route('admin.reports.scheduled.index')->with('success', 'Report schedule deleted.');
    }
}
