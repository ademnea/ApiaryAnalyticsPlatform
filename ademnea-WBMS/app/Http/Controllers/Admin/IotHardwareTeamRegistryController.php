<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIotHardwareTeamRequest;
use App\Http\Requests\Admin\UpdateIotHardwareTeamRequest;
use App\Models\IotHardwareTeam;
use App\Services\IotHardwareTeamRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IotHardwareTeamRegistryController extends Controller
{
    public function __construct(private readonly IotHardwareTeamRegistryService $service)
    {
        // $this->middleware('auth');
        // $this->middleware('permission:manage-iot-devices');
    }

    public function index(): View
    {
        $teams = $this->service->list();

        // View not yet implemented — interface task follows this one.
        return view('admin.hardware-teams.index', compact('teams'));
    }

    public function create(): View
    {
        return view('admin.hardware-teams.create');
    }

    public function store(StoreIotHardwareTeamRequest $request): RedirectResponse
    {
        $this->service->register($request->validated());

        return redirect()
            ->route('admin.hardware-teams.index')
            ->with('success', 'Hardware team registered successfully.');
    }

    public function show(IotHardwareTeam $hardwareTeam): View
    {
        $hardwareTeam->load('devices');

        return view('admin.hardware-teams.show', compact('hardwareTeam'));
    }

    public function edit(IotHardwareTeam $hardwareTeam): View
    {
        return view('admin.hardware-teams.edit', compact('hardwareTeam'));
    }

    public function update(UpdateIotHardwareTeamRequest $request, IotHardwareTeam $hardwareTeam): RedirectResponse
    {
        $this->service->update($hardwareTeam, $request->validated());

        return redirect()
            ->route('admin.hardware-teams.show', $hardwareTeam)
            ->with('success', 'Hardware team updated successfully.');
    }

    public function deactivate(IotHardwareTeam $hardwareTeam): RedirectResponse
    {
        $this->service->deactivate($hardwareTeam);

        return redirect()
            ->route('admin.hardware-teams.index')
            ->with('success', 'Hardware team deactivated. Its existing devices remain active.');
    }

    public function reactivate(IotHardwareTeam $hardwareTeam): RedirectResponse
    {
        $this->service->reactivate($hardwareTeam);

        return redirect()
            ->route('admin.hardware-teams.index')
            ->with('success', 'Hardware team reactivated.');
    }
}