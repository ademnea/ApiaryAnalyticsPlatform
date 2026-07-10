@extends('layouts.app')

@section('title', 'Assign Device to Hive')
@section('page-title', 'Assign ' . $iotDevice->device_code . ' to a Hive')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.iot-devices.index') }}">IoT Devices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.iot-devices.show', $iotDevice) }}">{{ $iotDevice->device_code }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Assign to Hive</li>
@endsection

@section('content')

<div class="alert-ademnea mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Choose the apiary this device is being deployed to, then choose one hive within it. Only hives
    that don't already carry a <strong>{{ str_replace('_', ' ', $iotDevice->device_type) }}</strong> device are shown.
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><span class="badge bg-secondary me-2">1</span>Select Apiary</div>
            <div class="list-group list-group-flush" style="max-height:480px;overflow-y:auto;">
                @forelse($apiaries as $apiary)
                    <a href="#"
                       class="list-group-item list-group-item-action apiary-option"
                       hx-get="{{ route('admin.iot-devices.assign.hives', $iotDevice) }}?apiary_id={{ $apiary->id }}"
                       hx-target="#hive-step"
                       hx-swap="innerHTML"
                       onclick="document.querySelectorAll('.apiary-option').forEach(el => el.classList.remove('active')); this.classList.add('active');">
                        <div class="fw-medium">{{ $apiary->name }}</div>
                        <div class="text-muted" style="font-size:0.78rem;">
                            <i class="bi bi-person me-1"></i>{{ $apiary->farmer_name }} &nbsp;&bull;&nbsp; {{ $apiary->country }}
                        </div>
                    </a>
                @empty
                    <div class="p-3 text-muted text-center" style="font-size:0.82rem;">
                        No apiaries are registered yet. Register one under Apiary Management first.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><span class="badge bg-secondary me-2">2</span>Select Hive</div>
            <div class="card-body" id="hive-step">
                <div class="text-muted text-center py-5" style="font-size:0.82rem;">
                    <i class="bi bi-arrow-left d-block mb-2" style="font-size:1.3rem;"></i>
                    Choose an apiary on the left to see its available hives.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .apiary-option.active { background: var(--clr-forest-pale); border-color: var(--clr-forest-light); }
</style>
@endpush