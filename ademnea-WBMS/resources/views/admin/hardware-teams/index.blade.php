@extends('layouts.app')

@section('title', 'Hardware Teams')
@section('page-title', 'Hardware Teams')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Hardware Teams</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">
        Field teams responsible for deploying and maintaining IoT devices.
    </p>
    <a href="{{ route('admin.hardware-teams.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Register Hardware Team
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Team Name</th>
                    <th>Country</th>
                    <th>Contact</th>
                    <th>Members</th>
                    <th>Devices</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teams as $team)
                    <tr>
                        <td>
                            <a href="{{ route('admin.hardware-teams.show', $team) }}" class="text-decoration-none fw-medium" style="color:#1a2e1f;">
                                {{ $team->name }}
                            </a>
                        </td>
                        <td>{{ $team->country }}</td>
                        <td class="text-muted">
                            {{ $team->contact_email ?: '—' }}
                            @if($team->contact_phone)<br><span style="font-size:0.75rem;">{{ $team->contact_phone }}</span>@endif
                        </td>
                        <td>{{ $team->members_count }}</td>
                        <td>{{ $team->devices_count }}</td>
                        <td>
                            @if($team->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge badge-offline">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('admin.hardware-teams.show', $team) }}" class="btn btn-sm btn-outline-forest" title="View team">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.hardware-teams.edit', $team) }}" class="btn btn-sm btn-outline-forest" title="Edit team">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($team->is_active)
                                <form action="{{ route('admin.hardware-teams.deactivate', $team) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Deactivate {{ $team->name }}? It will stop receiving alert dispatches. Existing devices stay active.');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate team"><i class="bi bi-pause-circle"></i></button>
                                </form>
                            @else
                                <form action="{{ route('admin.hardware-teams.reactivate', $team) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-forest" title="Reactivate team"><i class="bi bi-play-circle"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No hardware teams registered yet. <a href="{{ route('admin.hardware-teams.create') }}">Register the first one</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection