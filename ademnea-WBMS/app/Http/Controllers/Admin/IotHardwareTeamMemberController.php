<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIotHardwareTeamMemberRequest;
use App\Http\Requests\Admin\UpdateIotHardwareTeamMemberRequest;
use App\Models\IotHardwareTeam;
use App\Models\IotHardwareTeamMember;
use App\Services\IotHardwareTeamMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IotHardwareTeamMemberController extends Controller
{
    public function __construct(private readonly IotHardwareTeamMemberService $service)
    {
        // $this->middleware('auth');
        // $this->middleware('permission:manage-iot-devices');
    }

    public function create(IotHardwareTeam $hardwareTeam): View
    {
        return view('admin.hardware-teams.members.create', compact('hardwareTeam'));
    }

    public function store(StoreIotHardwareTeamMemberRequest $request, IotHardwareTeam $hardwareTeam): RedirectResponse
    {
        $this->service->register($hardwareTeam, $request->validated());

        return redirect()
            ->route('admin.hardware-teams.show', $hardwareTeam)
            ->with('success', 'Team member added successfully.');
    }

    public function edit(IotHardwareTeam $hardwareTeam, IotHardwareTeamMember $member): View
    {
        return view('admin.hardware-teams.members.edit', compact('hardwareTeam', 'member'));
    }

    public function update(UpdateIotHardwareTeamMemberRequest $request, IotHardwareTeam $hardwareTeam, IotHardwareTeamMember $member): RedirectResponse
    {
        $this->service->update($member, $request->validated());

        return redirect()
            ->route('admin.hardware-teams.show', $hardwareTeam)
            ->with('success', 'Team member updated successfully.');
    }

    public function deactivate(IotHardwareTeam $hardwareTeam, IotHardwareTeamMember $member): RedirectResponse
    {
        $this->service->deactivate($member);

        return redirect()
            ->route('admin.hardware-teams.show', $hardwareTeam)
            ->with('success', 'Team member marked inactive.');
    }

    public function reactivate(IotHardwareTeam $hardwareTeam, IotHardwareTeamMember $member): RedirectResponse
    {
        $this->service->reactivate($member);

        return redirect()
            ->route('admin.hardware-teams.show', $hardwareTeam)
            ->with('success', 'Team member reactivated.');
    }
}