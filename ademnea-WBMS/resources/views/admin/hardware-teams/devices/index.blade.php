@extends('layouts.app')

@section('title', $hardwareTeam->name . ' — Devices')
@section('page-title', 'Devices — ' . $hardwareTeam->name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.index') }}">Hardware Teams</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.show', $hardwareTeam) }}">{{ $hardwareTeam->name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Devices</li>
@endsection

@section('content')
@include('admin.iot-devices._styles')

@include('admin.iot-devices._api-key-notice')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">All devices registered under <strong>{{ $hardwareTeam->name }}</strong>.</p>
    <a href="{{ route('admin.hardware-teams.devices.create', $hardwareTeam) }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Device
    </a>
</div>

<div class="card">
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
                @forelse($devices as $device)
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
@endsection
