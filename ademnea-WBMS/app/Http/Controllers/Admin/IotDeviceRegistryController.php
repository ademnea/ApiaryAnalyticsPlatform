<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ApiaryDirectoryServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIotDeviceRequest;
use App\Http\Requests\Admin\UpdateIotDeviceRequest;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Services\IotDeviceRegistryService;
use App\Services\IotHardwareTeamRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IotDeviceRegistryController extends Controller
{
    public function __construct(
        private readonly IotDeviceRegistryService $service,
        private readonly IotHardwareTeamRegistryService $teamService,
        private readonly ApiaryDirectoryServiceContract $apiaryDirectory,
    ) {
        // $this->middleware('auth');
        // $this->middleware('permission:manage-iot-devices');
    }

    // ---- Global device registry (IoT & Monitoring > Device Registry) ----

    public function index(Request $request): View
    {
        $devices = $this->service->list($request->only(['hardware_team_id', 'status', 'active_flag']));
        $hardwareTeams = $this->teamService->list();

        return view('admin.iot-devices.index', compact('devices', 'hardwareTeams'));
    }

    public function create(): View
    {
        $hardwareTeams = $this->teamService->list();

        return view('admin.iot-devices.create', compact('hardwareTeams'));
    }

    public function store(StoreIotDeviceRequest $request): RedirectResponse
    {
        $result = $this->service->register($request->validated());

        return redirect()
            ->route('admin.iot-devices.show', $result['device'])
            ->with('success', 'Device provisioned successfully.')
            ->with('plaintext_api_key', $result['plaintext_key']);
    }

    public function show(IotDevice $iotDevice): View
    {
        $iotDevice->load('hardwareTeam');

        return view('admin.iot-devices.show', compact('iotDevice'));
    }

    public function edit(IotDevice $iotDevice): View
    {
        $hardwareTeams = $this->teamService->list();

        return view('admin.iot-devices.edit', compact('iotDevice', 'hardwareTeams'));
    }

    public function update(UpdateIotDeviceRequest $request, IotDevice $iotDevice): RedirectResponse
    {
        $this->service->update($iotDevice, $request->validated());

        return redirect()
            ->route('admin.iot-devices.show', $iotDevice)
            ->with('success', 'Device updated successfully.');
    }

    public function revoke(IotDevice $iotDevice): RedirectResponse
    {
        $this->service->revoke($iotDevice);

        return redirect()->back()->with('success', 'Device access revoked. It can no longer submit data.');
    }

    public function reactivate(IotDevice $iotDevice): RedirectResponse
    {
        $this->service->reactivate($iotDevice);

        return redirect()->back()->with('success', 'Device reactivated.');
    }

    public function destroy(IotDevice $iotDevice): RedirectResponse
    {
        $this->service->delete($iotDevice);

        return redirect()
            ->route('admin.iot-devices.index')
            ->with('success', 'Device removed from the active registry.');
    }

    // ---- Team-scoped device flow ("Add Device" button on a team's page) ----

    public function indexForTeam(IotHardwareTeam $hardwareTeam): View
    {
        $devices = $this->service->list(['hardware_team_id' => $hardwareTeam->id]);

        return view('admin.hardware-teams.devices.index', compact('hardwareTeam', 'devices'));
    }

    public function createForTeam(IotHardwareTeam $hardwareTeam): View
    {
        return view('admin.hardware-teams.devices.create', compact('hardwareTeam'));
    }

    public function storeForTeam(StoreIotDeviceRequest $request, IotHardwareTeam $hardwareTeam): RedirectResponse
    {
        $result = $this->service->register($request->validated());

        return redirect()
            ->route('admin.hardware-teams.devices.index', $hardwareTeam)
            ->with('success', 'Device provisioned successfully.')
            ->with('plaintext_api_key', $result['plaintext_key'])
            ->with('new_device_id', $result['device']->id);
    }

    // ---- Device-to-hive assignment wizard ----
    // Reads apiary/hive data through ApiaryDirectoryServiceContract, currently
    // bound to a mock (App\Services\External\ApiaryDirectoryServiceMock).

    public function assignForm(IotDevice $iotDevice): View
    {
        abort_if(
            ! is_null($iotDevice->hive_id),
            403,
            'This device is already assigned to a hive. Unassign it before assigning a new one.'
        );

        $apiaries = $this->apiaryDirectory->listApiariesWithPrimaryFarmer();

        return view('admin.iot-devices.assign', compact('iotDevice', 'apiaries'));
    }

    public function assignHives(Request $request, IotDevice $iotDevice): View
    {
        $apiaryId = (int) $request->query('apiary_id');

        $apiary = $this->apiaryDirectory->listApiariesWithPrimaryFarmer()->firstWhere('id', $apiaryId);
        $hives = $apiary
            ? $this->apiaryDirectory->listHivesAvailableForDeviceType($apiaryId, $iotDevice->device_type)
            : collect();

        return view('admin.iot-devices.partials.assign-hives', [
            'iotDevice' => $iotDevice,
            'apiary' => $apiary,
            'apiaryId' => $apiaryId,
            'hives' => $hives,
        ]);
    }

    public function assign(Request $request, IotDevice $iotDevice): RedirectResponse
    {
        $validated = $request->validate(['hive_id' => ['required', 'integer']]);

        $this->service->assignToHive($iotDevice, $validated['hive_id']);

        return redirect()
            ->route('admin.iot-devices.show', $iotDevice)
            ->with('success', 'Device assigned to hive successfully.');
    }

    public function unassign(IotDevice $iotDevice): RedirectResponse
    {
        $this->service->unassignFromHive($iotDevice);

        return redirect()
            ->route('admin.iot-devices.show', $iotDevice)
            ->with('success', 'Device unassigned from its hive.');
    }
}