@extends('layouts.app')

@section('title', 'IoT Device Registry')
@section('page-title', 'IoT Device Registry')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">IoT Devices</li>
@endsection

@push('styles')
    <style>
        .health-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .health-pill .dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto; }
        .health-pill.is-online .dot  { background: #2D6A4F; }
        .health-pill.is-warning .dot { background: #D4A017; }
        .health-pill.is-offline .dot { background: #b30000; }
        .health-pill.is-online  { color: #2D6A4F; }
        .health-pill.is-warning { color: #856404; }
        .health-pill.is-offline { color: #7F1D1D; }
        .health-meta { font-size: 0.7rem; color: var(--clr-muted); }
    </style>
@endpush

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Hardware Team</label>
                <select name="hardware_team_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All teams</option>
                    @foreach($hardwareTeams as $team)
                        <option value="{{ $team->id }}" @selected(request('hardware_team_id') == $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach(['provisioned', 'deployed', 'offline', 'retired'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Access</label>
                <select name="active_flag" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="1" @selected(request('active_flag') === '1')>Active only</option>
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
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $devices->total() }} device(s) found.</p>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.devices.fleet') }}" class="btn btn-sm btn-outline-forest">
            <i class="bi bi-grid-3x3-gap me-1"></i>Device Fleet
        </a>
        <a href="{{ route('admin.iot-devices.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Register Device</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Device Code</th><th>Type</th><th>Team</th><th>Assigned Hive</th><th>Health</th><th>Status</th><th>Access</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    @php($deviceHealth = $health[$device->id])
                    <tr>
                        <td class="fw-medium">
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="text-decoration-none" title="View device health">
                                {{ $device->device_code }}
                            </a>
                        </td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $device->device_type) }}</td>
                        <td><a href="{{ route('admin.hardware-teams.show', $device->hardware_team_id) }}" class="text-decoration-none">{{ $device->hardwareTeam->name }}</a></td>
                        <td>
                            @if($device->hive_id)
                                <span class="badge badge-active"><i class="bi bi-geo-alt-fill me-1"></i>Hive #{{ $device->hive_id }}</span>
                            @else
                                <span class="badge badge-pending">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            @if(! $device->active_flag)
                                <span class="health-meta">—</span>
                            @else
                                @php($healthClass = match($deviceHealth['status']) { 'offline' => 'is-offline', 'warning' => 'is-warning', default => 'is-online' })
                                <a href="{{ route('admin.iot-devices.show', $device) }}" class="health-pill {{ $healthClass }}" style="text-decoration:none;">
                                    <span class="dot"></span>{{ ucfirst($deviceHealth['status']) }}
                                </a>
                                <div class="health-meta">
                                    @if($deviceHealth['battery_level'] !== null)
                                        <i class="bi bi-battery-half"></i> {{ (int) $deviceHealth['battery_level'] }}%
                                    @endif
                                    @if($deviceHealth['signal_strength'] !== null)
                                        &middot; <i class="bi bi-reception-4"></i> {{ (int) $deviceHealth['signal_strength'] }} dBm
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="text-capitalize">{{ $device->status }}</td>
                        <td>
                            @if($device->active_flag)<span class="badge badge-active">Active</span>
                            @else<span class="badge badge-offline">Revoked</span>@endif
                        </td>
                        <td class="text-end pe-3">@include('admin.iot-devices._row-actions', ['device' => $device])</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No devices match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($devices->hasPages())
        <div class="card-footer bg-white">{{ $devices->links() }}</div>
    @endif
</div>
@endsection