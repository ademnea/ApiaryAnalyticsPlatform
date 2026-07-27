@extends('layouts.app')

@section('title', $hardwareTeam->name)
@section('page-title', $hardwareTeam->name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.index') }}">Hardware Teams</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $hardwareTeam->name }}</li>
@endsection

@section('content')

{{-- One-time API key banner: only appears right after a device is
     provisioned from this team's "Add Device" flow. Never shown again. --}}
@if(session('plaintext_api_key'))
    <div class="alert-ademnea mb-3" role="alert">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <strong><i class="bi bi-key me-1"></i> Device API Key — copy this now</strong>
                <p class="mb-1 mt-1">This key will not be shown again. Install it on the Raspberry Pi as <code>X-Api-Key</code>.</p>
                <code style="font-size:0.95rem;background:#fff;padding:0.35rem 0.6rem;border-radius:6px;display:inline-block;">
                    {{ session('plaintext_api_key') }}
                </code>
            </div>
            <button type="button" class="btn btn-sm btn-outline-forest"
                    onclick="navigator.clipboard.writeText('{{ session('plaintext_api_key') }}'); this.innerText='Copied';">
                <i class="bi bi-clipboard me-1"></i>Copy
            </button>
        </div>
    </div>
@endif

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
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hardware-teams.edit', $hardwareTeam) }}" class="btn btn-outline-forest">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @if($hardwareTeam->is_active)
                <form action="{{ route('admin.hardware-teams.deactivate', $hardwareTeam) }}" method="POST"
                      onsubmit="return confirm('Deactivate this team? It will stop receiving alert dispatches. Its devices stay active.');">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-pause-circle me-1"></i>Deactivate</button>
                </form>
            @else
                <form action="{{ route('admin.hardware-teams.reactivate', $hardwareTeam) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary"><i class="bi bi-play-circle me-1"></i>Reactivate</button>
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
                <div class="stat-label">Registered Devices</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon honey"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-value">{{ $hardwareTeam->members->where('is_active', true)->count() }}</div>
                <div class="stat-label">Active Team Members</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-hexagon"></i></div>
            <div>
                <div class="stat-value">{{ $hardwareTeam->devices->whereNotNull('hive_id')->count() }}</div>
                <div class="stat-label">Devices Deployed to Hives</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cpu me-1"></i>Devices</span>
        <a href="{{ route('admin.hardware-teams.devices.create', $hardwareTeam) }}" class="btn btn-honey btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Add Device
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Device Code</th><th>Type</th><th>Assigned Hive</th><th>Status</th><th>Access</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hardwareTeam->devices as $device)
                    <tr @if((int) session('new_device_id') === $device->id) style="background:#FFFBF0;" @endif>
                        <td class="fw-medium">
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="text-decoration-none" style="color:#1a2e1f;">
                                {{ $device->device_code }}
                            </a>
                        </td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $device->device_type) }}</td>
                        <td>
                            @if($device->hive_id)
                                {{-- Placeholder label — Apiary module supplies the real hive display field --}}
                                <span class="badge badge-active"><i class="bi bi-geo-alt-fill me-1"></i>Hive #{{ $device->hive_id }}</span>
                            @else
                                <span class="badge badge-pending">Unassigned</span>
                            @endif
                        </td>
                        <td class="text-capitalize">{{ $device->status }}</td>
                        <td>
                            @if($device->active_flag)<span class="badge badge-active">Active</span>
                            @else<span class="badge badge-offline">Revoked</span>@endif
                        </td>
                        <td class="text-end pe-3">
                            @include('admin.iot-devices._row-actions', ['device' => $device])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No devices registered under this team yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-1"></i>Team Members</span>
        <a href="{{ route('admin.hardware-teams.members.create', $hardwareTeam) }}" class="btn btn-honey btn-sm">
            <i class="bi bi-person-plus me-1"></i>Add Member
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th><th>Team Role</th><th>Profession</th><th>Country</th><th>Contact</th><th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hardwareTeam->members as $member)
                    <tr>
                        <td class="fw-medium">{{ $member->name }}</td>
                        <td>{{ $member->team_role ?: '—' }}</td>
                        <td>{{ $member->profession ?: '—' }}</td>
                        <td>{{ $member->country ?: '—' }}</td>
                        <td class="text-muted" style="font-size:0.78rem;">
                            @if($member->email)<div><i class="bi bi-envelope me-1"></i>{{ $member->email }}</div>@endif
                            @if($member->phone)<div><i class="bi bi-telephone me-1"></i>{{ $member->phone }}</div>@endif
                            @if(!$member->email && !$member->phone)—@endif
                        </td>
                        <td>
                            @if($member->is_active)<span class="badge badge-active">Active</span>
                            @else<span class="badge badge-offline">Inactive</span>@endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('admin.hardware-teams.members.edit', [$hardwareTeam, $member]) }}"
                               class="btn btn-sm btn-outline-forest" title="Edit member"><i class="bi bi-pencil"></i></a>
                            @if($member->is_active)
                                <form action="{{ route('admin.hardware-teams.members.deactivate', [$hardwareTeam, $member]) }}"
                                      method="POST" class="d-inline" onsubmit="return confirm('Mark {{ $member->name }} as inactive?');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate member"><i class="bi bi-person-dash"></i></button>
                                </form>
                            @else
                                <form action="{{ route('admin.hardware-teams.members.reactivate', [$hardwareTeam, $member]) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-forest" title="Reactivate member"><i class="bi bi-person-check"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No team members recorded yet. Add the people responsible for deploying and
                            maintaining this team's devices so admins know who to contact.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection