@extends('layouts.app')

@section('title', $iotDevice->device_code)
@section('page-title', $iotDevice->device_code)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.iot-devices.index') }}">IoT Devices</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $iotDevice->device_code }}</li>
@endsection

@section('content')
@include('admin.iot-devices._styles')

@include('admin.iot-devices._api-key-notice')

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cpu me-1"></i>Device Info</span>
                @if($iotDevice->active_flag)<span class="badge badge-active"><i class="bi bi-unlock me-1"></i>Data access allowed</span>
                @else<span class="badge badge-offline"><i class="bi bi-lock me-1"></i>Data access revoked</span>@endif
            </div>
            <div class="card-body">
                <dl class="row mb-0 device-facts" style="font-size:0.85rem;">
                    <dt class="col-sm-4">Device Code</dt>
                    <dd class="col-sm-8 fw-medium">{{ $iotDevice->device_code }}</dd>

                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8">
                        <span class="text-capitalize">{{ str_replace('_', ' ', $iotDevice->device_type) }}</span>
                        <span class="fact-hint">{{ match($iotDevice->device_type) {
                            'numeric_sensor' => 'Sends temperature, humidity, CO₂ or weight readings',
                            'media_capture' => 'Sends audio, video or photos',
                            'combo' => 'Sends both readings and media',
                            default => '',
                        } }}</span>
                    </dd>

                    <dt class="col-sm-4">Hardware Team</dt>
                    <dd class="col-sm-8"><a href="{{ route('admin.hardware-teams.show', $iotDevice->hardwareTeam) }}">{{ $iotDevice->hardwareTeam->name }}</a></dd>

                    <dt class="col-sm-4">Assigned Hive</dt>
                    <dd class="col-sm-8">
                        @if($iotDevice->hive)
                            <a href="{{ route('admin.hives.show', $iotDevice->hive) }}">{{ $iotDevice->hive->display_name ?? $iotDevice->hive->hive_code }}</a>
                            @if($iotDevice->hive->display_name && $iotDevice->hive->hive_code)
                                <span class="fact-hint">{{ $iotDevice->hive->hive_code }}</span>
                            @endif
                        @elseif($iotDevice->hive_id)
                            Hive #{{ $iotDevice->hive_id }}
                        @else
                            <span class="text-muted">Not on a hive yet</span>
                            <a href="{{ route('admin.iot-devices.assign.form', $iotDevice) }}" class="btn btn-sm btn-primary ms-2">
                                <i class="bi bi-geo-alt me-1"></i>Assign to a hive
                            </a>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Lifecycle Stage</dt>
                    <dd class="col-sm-8">
                        @include('admin.iot-devices._lifecycle-badge', ['status' => $iotDevice->status, 'withHint' => true])
                    </dd>

                    <dt class="col-sm-4">Reporting Schedule</dt>
                    <dd class="col-sm-8">
                        Every {{ $iotDevice->expected_interval_minutes }} {{ Str::plural('minute', $iotDevice->expected_interval_minutes) }}
                        <span class="fact-hint">Flagged as silent if it misses this schedule</span>
                    </dd>

                    <dt class="col-sm-4">Firmware Version</dt>
                    <dd class="col-sm-8">{{ $iotDevice->firmware_version ?: 'Not reported by the device yet' }}</dd>

                    <dt class="col-sm-4">Hardware Revision</dt>
                    <dd class="col-sm-8">{!! $iotDevice->hardware_revision ? e($iotDevice->hardware_revision) : '<span class="text-muted">Not recorded</span>' !!}</dd>

                    <dt class="col-sm-4">Firmware Notes</dt>
                    <dd class="col-sm-8">{!! $iotDevice->firmware_notes ? e($iotDevice->firmware_notes) : '<span class="text-muted">None</span>' !!}</dd>

                    <dt class="col-sm-4">Registered</dt>
                    <dd class="col-sm-8 mb-0">{{ $iotDevice->created_at->format('d M Y, H:i') }}</dd>
                </dl>
            </div>
        </div>

        @include('admin.iot-devices._health')
    </div>

    <div class="col-lg-4">
        <div class="sticky-side">
        <div class="card">
            <div class="card-header">Actions</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.iot-devices.edit', $iotDevice) }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-pencil me-2 text-muted"></i>Edit device details
                </a>

                @if(is_null($iotDevice->hive_id))
                    <a href="{{ route('admin.iot-devices.assign.form', $iotDevice) }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-geo-alt me-2 text-muted"></i>Assign to a hive
                    </a>
                @else
                    <form action="{{ route('admin.iot-devices.unassign', $iotDevice) }}" method="POST"
                          onsubmit="return confirm('Unassign this device from its hive?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100">
                            <i class="bi bi-geo-alt-fill me-2 text-muted"></i>Unassign from hive
                            <span class="d-block text-muted ms-4" style="font-size:0.74rem;">Take it off its current hive so it can be moved</span>
                        </button>
                    </form>
                @endif

                @if($iotDevice->active_flag)
                    <form action="{{ route('admin.iot-devices.revoke', $iotDevice) }}" method="POST"
                          onsubmit="return confirm('Revoke this device\'s access? It will immediately stop being able to submit data.');">
                        @csrf @method('PATCH')
                        <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100 text-danger">
                            <i class="bi bi-slash-circle me-2"></i>Revoke data access
                            <span class="d-block text-muted ms-4" style="font-size:0.74rem;">Stop accepting data from this device</span>
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.iot-devices.reactivate', $iotDevice) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100">
                            <i class="bi bi-arrow-counterclockwise me-2 text-muted"></i>Reactivate data access
                            <span class="d-block text-muted ms-4" style="font-size:0.74rem;">Accept data from this device again</span>
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.iot-devices.destroy', $iotDevice) }}" method="POST"
                      onsubmit="return confirm('Remove this device from the active registry? Historical data is kept.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="list-group-item list-group-item-action text-start border-0 w-100 text-danger">
                        <i class="bi bi-trash me-2"></i>Remove device
                        <span class="d-block text-muted ms-4" style="font-size:0.74rem;">Take it out of the registry; its history is kept</span>
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
</div>
@endsection