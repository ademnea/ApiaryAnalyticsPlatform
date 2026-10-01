@extends('layouts.app')

@section('title', 'IoT Device Registry')
@section('page-title', 'IoT Device Registry')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">IoT Devices</li>
@endsection

@section('content')
@include('admin.iot-devices._styles')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="filter-team" class="form-label mb-1" style="font-size:0.75rem;">Hardware team</label>
                <select id="filter-team" name="hardware_team_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All teams</option>
                    @foreach($hardwareTeams as $team)
                        <option value="{{ $team->id }}" @selected(request('hardware_team_id') == $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter-status" class="form-label mb-1" style="font-size:0.75rem;">Lifecycle stage</label>
                <select id="filter-status" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All stages</option>
                    @foreach(['provisioned', 'deployed', 'offline', 'retired'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter-access" class="form-label mb-1" style="font-size:0.75rem;">Data access</label>
                <select id="filter-access" name="active_flag" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Allowed and revoked</option>
                    <option value="1" @selected(request('active_flag') === '1')>Allowed only</option>
                    <option value="0" @selected(request('active_flag') === '0')>Revoked only</option>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ route('admin.iot-devices.index') }}" class="btn btn-sm btn-outline-forest"><i class="bi bi-x-circle me-1"></i>Clear filters</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $devices->total() }} {{ Str::plural('device', $devices->total()) }} found.</p>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.devices.fleet') }}" class="btn btn-sm btn-outline-forest">
            <i class="bi bi-grid-3x3-gap me-1"></i>Device Fleet
        </a>
        <a href="{{ route('admin.iot-devices.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Register Device</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 device-table">
            <thead>
                <tr>
                    <th class="ps-3">Device</th>
                    <th>Team</th>
                    <th>Hive</th>
                    <th>Connection<span class="th-hint">Is it reporting right now?</span></th>
                    <th>Lifecycle<span class="th-hint">Where it is in deployment</span></th>
                    <th>Data access<span class="th-hint">May it send data?</span></th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="text-decoration-none fw-medium">{{ $device->device_code }}</a>
                            <div class="cell-sub text-capitalize">{{ str_replace('_', ' ', $device->device_type) }}</div>
                        </td>
                        <td class="cell-wrap"><a href="{{ route('admin.hardware-teams.show', $device->hardware_team_id) }}" class="text-decoration-none">{{ $device->hardwareTeam->name }}</a></td>
                        <td>@include('admin.iot-devices._hive-cell', ['device' => $device])</td>
                        <td>@include('admin.iot-devices._health-cell', ['device' => $device, 'deviceHealth' => $health[$device->id]])</td>
                        <td>@include('admin.iot-devices._lifecycle-badge', ['status' => $device->status])</td>
                        <td>
                            @if($device->active_flag)<span class="badge badge-active">Allowed</span>
                            @else<span class="badge badge-offline">Revoked</span>@endif
                        </td>
                        <td class="text-end pe-3">@include('admin.iot-devices._row-actions', ['device' => $device])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-cpu d-block mb-2" style="font-size:1.5rem;"></i>
                            @if(request()->hasAny(['hardware_team_id', 'status', 'active_flag']))
                                No devices match these filters.
                                <a href="{{ route('admin.iot-devices.index') }}">Clear filters</a>
                            @else
                                No devices registered yet.
                                <a href="{{ route('admin.iot-devices.create') }}">Register the first device</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($devices->hasPages())
        <div class="card-footer bg-white">{{ $devices->links() }}</div>
    @endif
</div>
@endsection