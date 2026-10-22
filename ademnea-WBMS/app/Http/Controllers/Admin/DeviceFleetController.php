<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Anomaly\DeviceFleetService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * REQ-F-IOT-14 / UC-IOT-09 (fleet half): unified Device Monitoring page.
 */
class DeviceFleetController extends Controller
{
    public function __construct(private readonly DeviceFleetService $fleet) {}

    public function index(Request $request): View
    {
        $section = $request->query('section', 'overview');
        $historyFilters = $request->validate([
            ...$this->historyFilterRules('battery'),
            ...$this->historyFilterRules('connectivity'),
        ]);

        $overview = $this->fleet->overview(
            [
                'health' => $request->query('health'),
                'hardware_team_id' => $request->integer('hardware_team_id') ?: null,
                'assignment' => $request->query('assignment'),
                'has_issues' => $request->boolean('has_issues'),
                'battery' => $request->query('battery'),
            ],
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => $request->url(), 'query' => $request->except('page')],
            array_intersect_key($historyFilters, array_flip(['battery_range', 'battery_device', 'battery_sort', 'battery_per_page'])),
            $section === 'battery',
            array_intersect_key($historyFilters, array_flip(['connectivity_range', 'connectivity_device', 'connectivity_sort', 'connectivity_per_page'])),
            $section === 'connectivity',
        );

        $overview['section'] = in_array($section, ['overview', 'battery', 'connectivity', 'issues'], true)
            ? $section
            : 'overview';

        return view('admin.devices.fleet', $overview);
    }

    public function exportReadings(Request $request, string $stream): StreamedResponse
    {
        abort_unless(in_array($stream, ['battery', 'connectivity'], true), Response::HTTP_NOT_FOUND);

        $filters = $request->validate($this->historyFilterRules($stream));

        return response()->streamDownload(function () use ($stream, $filters): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new \RuntimeException('Unable to open CSV output stream.');
            }

            foreach ($this->fleet->exportHistoryRows($stream, $filters) as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, "{$stream}-readings-".now(config('app.local_timezone'))->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function historyFilterRules(string $prefix): array
    {
        return [
            "{$prefix}_range" => ['nullable', Rule::in(['24h', '7d', '30d', '90d'])],
            "{$prefix}_device" => ['nullable', 'integer', Rule::exists('iot_devices', 'id')
                ->where('active_flag', true)
                ->whereNull('deleted_at')],
            "{$prefix}_sort" => ['nullable', Rule::in(['recorded', 'received'])],
            "{$prefix}_per_page" => ['nullable', 'integer', Rule::in([25, 50, 100])],
        ];
    }
}
