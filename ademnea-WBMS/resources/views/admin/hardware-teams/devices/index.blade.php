@extends('layouts.app')

@section('title', $hardwareTeam->name . ' — Devices')
@section('page-title', 'Devices — ' . $hardwareTeam->name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.index') }}">Hardware Teams</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.show', $hardwareTeam) }}">{{ $hardwareTeam->name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Devices</li>
@endsection

@section('content')

@if(session('plaintext_api_key'))
    <div class="alert-ademnea mb-3" role="alert">
        <strong><i class="bi bi-key me-1"></i>Device API Key — copy this now</strong>
        <p class="mb-1 mt-1">This key will not be shown again.</p>
        <code style="font-size:0.95rem;background:#fff;padding:0.35rem 0.6rem;border-radius:6px;display:inline-block;">
            {{ session('plaintext_api_key') }}
        </code>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">All devices registered under <strong>{{ $hardwareTeam->name }}</strong>.</p>
    <a href="{{ route('admin.hardware-teams.devices.create', $hardwareTeam) }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Device
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Device Code</th><th>Type</th><th>Assigned Hive</th><th>Status</th><th>Access</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    <tr @if((int) session('new_device_id') === $device->id) style="background:#FFFBF0;" @endif>
                        <td class="fw-medium">{{ $device->device_code }}</td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $device->device_type) }}</td>
                        <td>
                            @if($device->hive_id)
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
                        <td class="text-end pe-3">@include('admin.iot-devices._row-actions', ['device' => $device])</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No devices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection