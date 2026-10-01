@extends('layouts.app')

@section('title', $hardwareTeam->name)
@section('page-title', $hardwareTeam->name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.index') }}">Hardware Teams</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $hardwareTeam->name }}</li>
@endsection

@section('content')
@include('admin.iot-devices._styles')

{{-- One-time API key banner: only appears right after a device is
     provisioned from this team's "Add Device" flow. Never shown again. --}}
@include('admin.iot-devices._api-key-notice')

<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 style="font-size:1.05rem;font-weight:600;margin:0;">{{ $hardwareTeam->name }}</h2>
                @if($hardwareTeam->is_active)
                    <span class="badge badge-active">Active</span>
                @else
                    <span class="badge badge-offline">Inactive</span>
                @endif
            </div>
            <p class="text-muted mb-0" style="font-size:0.82rem;">
                <i class="bi bi-geo-alt me-1"></i>{{ $hardwareTeam->country }}
                @if($hardwareTeam->contact_email)&nbsp;&bull;&nbsp;<i class="bi bi-envelope me-1"></i>{{ $hardwareTeam->contact_email }}@endif
                @if($hardwareTeam->contact_phone)&nbsp;&bull;&nbsp;<i class="bi bi-telephone me-1"></i>{{ $hardwareTeam->contact_phone }}@endif
                @if(!$hardwareTeam->contact_email && !$hardwareTeam->contact_phone)
                    &nbsp;&bull;&nbsp;No team contact set —
                    <a href="{{ route('admin.hardware-teams.edit', $hardwareTeam) }}">add one</a> so alerts reach the team
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hardware-teams.edit', $hardwareTeam) }}" class="btn btn-outline-forest">
                <i class="bi bi-pencil me-1"></i>Edit team
            </a>
            @if($hardwareTeam->is_active)
                <form action="{{ route('admin.hardware-teams.deactivate', $hardwareTeam) }}" method="POST"
                      onsubmit="return confirm('Deactivate this team? It will stop receiving alert dispatches. Its devices stay active.');">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-pause-circle me-1"></i>Deactivate team</button>
                </form>
            @else
                <form action="{{ route('admin.hardware-teams.reactivate', $hardwareTeam) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary"><i class="bi bi-play-circle me-1"></i>Reactivate team</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-cpu"></i></div>
            <div>
                <div class="stat-value">{{ $hardwareTeam->devices->count() }}</div>
                <div class="stat-label">Devices owned by this team</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon honey"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-value">{{ $hardwareTeam->members->where('is_active', true)->count() }}</div>
                <div class="stat-label">Active members (receive alerts)</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-hexagon"></i></div>
            <div>
                <div class="stat-value">{{ $hardwareTeam->devices->whereNotNull('hive_id')->count() }}</div>
                <div class="stat-label">Devices installed on a hive</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cpu me-1"></i>Devices <span class="text-muted fw-normal">({{ $hardwareTeam->devices->count() }})</span></span>
        <a href="{{ route('admin.hardware-teams.devices.create', $hardwareTeam) }}" class="btn btn-honey btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Device
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 device-table">
            <thead>
                <tr>
                    <th class="ps-3">Device</th>
                    <th>Hive</th>
                    <th>Lifecycle<span class="th-hint">Where it is in deployment</span></th>
                    <th>Data access<span class="th-hint">May it send data?</span></th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hardwareTeam->devices as $device)
                    <tr @if((int) session('new_device_id') === $device->id) style="background:#FFFBF0;" @endif>
                        <td class="ps-3">
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="text-decoration-none fw-medium">{{ $device->device_code }}</a>
                            <div class="cell-sub text-capitalize">{{ str_replace('_', ' ', $device->device_type) }}</div>
                        </td>
                        <td>@include('admin.iot-devices._hive-cell', ['device' => $device])</td>
                        <td>@include('admin.iot-devices._lifecycle-badge', ['status' => $device->status])</td>
                        <td>
                            @if($device->active_flag)<span class="badge badge-active">Allowed</span>
                            @else<span class="badge badge-offline">Revoked</span>@endif
                        </td>
                        <td class="text-end pe-3">@include('admin.iot-devices._row-actions', ['device' => $device])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-cpu d-block mb-2" style="font-size:1.5rem;"></i>
                            This team has no devices yet.
                            <a href="{{ route('admin.hardware-teams.devices.create', $hardwareTeam) }}">Add the first device</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-1"></i>Team Members <span class="text-muted fw-normal">({{ $hardwareTeam->members->count() }})</span></span>
        <a href="{{ route('admin.hardware-teams.members.create', $hardwareTeam) }}" class="btn btn-honey btn-sm">
            <i class="bi bi-person-plus me-1"></i>Add Member
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 device-table">
            <thead>
                <tr>
                    <th class="ps-3">Member</th>
                    <th>Profession</th>
                    <th>Country</th>
                    <th>Contact<span class="th-hint">Where their alerts are sent</span></th>
                    <th>Status<span class="th-hint">Active members receive alerts</span></th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hardwareTeam->members as $member)
                    <tr>
                        <td class="ps-3 cell-wrap">
                            <span class="fw-medium">{{ $member->name }}</span>
                            <div class="cell-sub">{{ $member->team_role ?: 'No role recorded' }}</div>
                        </td>
                        <td class="cell-wrap">{!! $member->profession ? e($member->profession) : '<span class="text-muted">Not recorded</span>' !!}</td>
                        <td>{!! $member->country ? e($member->country) : '<span class="text-muted">Not recorded</span>' !!}</td>
                        <td>
                            @if($member->email)<div><i class="bi bi-envelope me-1 text-muted"></i>{{ $member->email }}</div>@endif
                            @if($member->phone)<div class="cell-sub"><i class="bi bi-telephone me-1"></i>{{ $member->phone }}</div>@endif
                            @if(!$member->email && !$member->phone)<span class="text-muted">No contact set</span>@endif
                        </td>
                        <td>
                            @if($member->is_active)<span class="badge badge-active">Active</span>
                            @else<span class="badge badge-offline">Inactive</span>@endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('admin.hardware-teams.members.edit', [$hardwareTeam, $member]) }}" class="btn btn-sm btn-outline-forest">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                                @if($member->is_active)
                                    <form action="{{ route('admin.hardware-teams.members.deactivate', [$hardwareTeam, $member]) }}"
                                          method="POST" onsubmit="return confirm('Mark {{ $member->name }} as inactive? They will stop receiving this team\'s alerts.');">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash me-1"></i>Deactivate</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.hardware-teams.members.reactivate', [$hardwareTeam, $member]) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-forest"><i class="bi bi-person-check me-1"></i>Reactivate</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-people d-block mb-2" style="font-size:1.5rem;"></i>
                            No team members recorded yet. Add the people responsible for this team's devices
                            so admins know who to contact.
                            <a href="{{ route('admin.hardware-teams.members.create', $hardwareTeam) }}">Add the first member</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
