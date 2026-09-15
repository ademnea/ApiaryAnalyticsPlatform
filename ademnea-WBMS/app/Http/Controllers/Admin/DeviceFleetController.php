<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Anomaly\DeviceFleetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * REQ-F-IOT-14 / UC-IOT-09 (fleet half): Device Fleet page.
 */
class DeviceFleetController extends Controller
{
    public function __construct(private readonly DeviceFleetService $fleet)
    {
    }

    public function index(Request $request): View
    {
        $overview = $this->fleet->overview(
            [
                'health' => $request->query('health'),
                'hardware_team_id' => $request->integer('hardware_team_id') ?: null,
                'assignment' => $request->query('assignment'),
                'has_issues' => $request->boolean('has_issues'),
            ],
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.devices.fleet', $overview);
    }
}
