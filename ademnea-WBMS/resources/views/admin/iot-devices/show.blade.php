@extends('layouts.app')

@section('title', $iotDevice->device_code)
@section('page-title', $iotDevice->device_code)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.iot-devices.index') }}">IoT Devices</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $iotDevice->device_code }}</li>
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

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cpu me-1"></i>Device Info</span>
                @if($iotDevice->active_flag)<span class="badge badge-active">Active</span>
                @else<span class="badge badge-offline">Revoked</span>@endif
            </div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Device Code</dt>
                    <dd class="col-sm-8">{{ $iotDevice->device_code }}</dd>

                    <dt class="col-sm-4 text-muted">Type</dt>
                    <dd class="col-sm-8 text-capitalize">{{ str_replace('_', ' ', $iotDevice->device_type) }}</dd>

                    <dt class="col-sm-4 text-muted">Hardware Team</dt>
                    <dd class="col-sm-8"><a href="{{ route('admin.hardware-teams.show', $iotDevice->hardwareTeam) }}">{{ $iotDevice->hardwareTeam->name }}</a></dd>

                    <dt class="col-sm-4 text-muted">Assigned Hive</dt>
                    <dd class="col-sm-8">
                        @if($iotDevice->hive_id)
                            {{-- Placeholder — Apiary module supplies the real display field --}}
                            Hive #{{ $iotDevice->hive_id }}
                        @else
                            <span class="text-muted">Not yet assigned</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Lifecycle Status</dt>
                    <dd class="col-sm-8 text-capitalize">{{ $iotDevice->status }}</dd>

                    <dt class="col-sm-4 text-muted">Expected Interval</dt>
                    <dd class="col-sm-8">{{ $iotDevice->expected_interval_minutes }} minute(s)</dd>

                    <dt class="col-sm-4 text-muted">Firmware Version</dt>
                    <dd class="col-sm-8">{{ $iotDevice->firmware_version ?: 'Not yet reported' }}</dd>

                    <dt class="col-sm-4 text-muted">Hardware Revision</dt>
                    <dd class="col-sm-8">{{ $iotDevice->hardware_revision ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Firmware Notes</dt>
                    <dd class="col-sm-8">{{ $iotDevice->firmware_notes ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Registered</dt>
                    <dd class="col-sm-8">{{ $iotDevice->created_at->format('d M Y, H:i') }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-activity me-1"></i>Latest Telemetry</div>
            <div class="card-body text-muted text-center py-4" style="font-size:0.82rem;">
                <i class="bi bi-hourglass-split d-block mb-2" style="font-size:1.5rem;"></i>
                Telemetry display is provided by the IoT Condition Monitoring module.
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Actions</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.iot-devices.edit', $iotDevice) }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-pencil me-2 text-muted"></i>Edit Device
                </a>

                @if(is_null($iotDevice->hive_id))
                    <a href="{{ route('admin.iot-devices.assign.form', $iotDevice) }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-geo-alt me-2 text-muted"></i>Assign to Hive
                    </a>
                @else
                    <form action="{{ route('admin.iot-devices.unassign', $iotDevice) }}" method="POST"
                          onsubmit="return confirm('Unassign this device from its hive?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100">
                            <i class="bi bi-geo-alt-fill me-2 text-muted"></i>Unassign from Hive
                        </button>
                    </form>
                @endif

                @if($iotDevice->active_flag)
                    <form action="{{ route('admin.iot-devices.revoke', $iotDevice) }}" method="POST"
                          onsubmit="return confirm('Revoke this device\'s access? It will immediately stop being able to submit data.');">
                        @csrf @method('PATCH')
                        <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100 text-danger">
                            <i class="bi bi-slash-circle me-2"></i>Revoke Access
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.iot-devices.reactivate', $iotDevice) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100">
                            <i class="bi bi-arrow-counterclockwise me-2 text-muted"></i>Reactivate Access
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.iot-devices.destroy', $iotDevice) }}" method="POST"
                      onsubmit="return confirm('Remove this device from the active registry? Historical data is kept.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100 text-danger">
                        <i class="bi bi-trash me-2"></i>Remove Device
                    </button>
                </form>
            </div>
        </div>

        @if(!$iotDevice->active_flag)
            <div class="alert alert-warning mt-3" style="font-size:0.8rem;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                This device's access is revoked. It cannot submit heartbeats, sensor data, or media until reactivated.
            </div>
        @endif
    </div>
</div>
@endsection