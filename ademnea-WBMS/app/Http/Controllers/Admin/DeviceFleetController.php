<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Anomaly\DeviceFleetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * REQ-F-IOT-14 / UC-IOT-09 (fleet half): unified Device Monitoring page.
 */
class DeviceFleetController extends Controller
{
    public function __construct(private readonly DeviceFleetService $fleet) {}

    public function index(Request $request): View
    {
        $overview = $this->fleet->overview(
            [
                'health' => $request->query('health'),
                'hardware_team_id' => $request->integer('hardware_team_id') ?: null,
                'assignment' => $request->query('assignment'),
                'has_issues' => $request->boolean('has_issues'),
                'battery' => $request->query('battery'),
            ],
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $section = $request->query('section', 'overview');
        $overview['section'] = in_array($section, ['overview', 'battery', 'connectivity', 'issues'], true)
            ? $section
            : 'overview';

        return view('admin.devices.fleet', $overview);
    }
}
