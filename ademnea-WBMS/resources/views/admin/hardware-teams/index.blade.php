@extends('layouts.app')

@section('title', 'Hardware Teams')
@section('page-title', 'Hardware Teams')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Hardware Teams</li>
@endsection

@section('content')
@include('admin.iot-devices._styles')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">
        The teams that install and maintain IoT devices, and who are alerted when a device has a problem.
    </p>
    <a href="{{ route('admin.hardware-teams.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Register Hardware Team
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 device-table">
            <thead>
                <tr>
                    <th class="ps-3">Team</th>
                    <th>Contact<span class="th-hint">Where alerts are sent</span></th>
                    <th>Members</th>
                    <th>Devices</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teams as $team)
                    <tr>
                        <td class="ps-3 cell-wrap">
                            <a href="{{ route('admin.hardware-teams.show', $team) }}" class="text-decoration-none fw-medium">{{ $team->name }}</a>
                            <div class="cell-sub"><i class="bi bi-geo-alt me-1"></i>{{ $team->country }}</div>
                        </td>
                        <td>
                            @if($team->contact_email || $team->contact_phone)
                                @if($team->contact_email)<div><i class="bi bi-envelope me-1 text-muted"></i>{{ $team->contact_email }}</div>@endif
                                @if($team->contact_phone)<div class="cell-sub"><i class="bi bi-telephone me-1"></i>{{ $team->contact_phone }}</div>@endif
                            @else
                                <span class="text-muted">No contact set</span>
                                <div class="cell-sub"><a href="{{ route('admin.hardware-teams.edit', $team) }}" class="text-decoration-none">Add one</a></div>
                            @endif
                        </td>
                        <td>{{ $team->members_count }} {{ Str::plural('member', $team->members_count) }}</td>
                        <td>
                            <a href="{{ route('admin.hardware-teams.devices.index', $team) }}" class="text-decoration-none">
                                {{ $team->devices_count }} {{ Str::plural('device', $team->devices_count) }}
                            </a>
                        </td>
                        <td>
                            @if($team->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge badge-offline">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('admin.hardware-teams.show', $team) }}" class="btn btn-sm btn-outline-forest">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-forest dropdown-toggle" data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
                                            aria-label="More actions for {{ $team->name }}">
                                        More
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.hardware-teams.edit', $team) }}">
                                                <i class="bi bi-pencil me-1"></i>Edit details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.hardware-teams.devices.create', $team) }}">
                                                <i class="bi bi-plus-circle me-1"></i>Add a device
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.hardware-teams.members.create', $team) }}">
                                                <i class="bi bi-person-plus me-1"></i>Add a member
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            @if($team->is_active)
                                                <form action="{{ route('admin.hardware-teams.deactivate', $team) }}" method="POST"
                                                      onsubmit="return confirm('Deactivate {{ $team->name }}? It will stop receiving alert dispatches. Existing devices stay active.');">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-pause-circle me-1"></i>Deactivate team</button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.hardware-teams.reactivate', $team) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-play-circle me-1"></i>Reactivate team</button>
                                                </form>
                                            @endif
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-people d-block mb-2" style="font-size:1.5rem;"></i>
                            No hardware teams registered yet.
                            <a href="{{ route('admin.hardware-teams.create') }}">Register the first one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
