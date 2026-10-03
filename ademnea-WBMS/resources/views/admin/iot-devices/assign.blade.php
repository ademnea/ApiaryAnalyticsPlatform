@extends('layouts.app')

@section('title', 'Assign Device to Hive')
@section('page-title', 'Assign ' . $iotDevice->device_code . ' to a Hive')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.iot-devices.index') }}">IoT Devices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.iot-devices.show', $iotDevice) }}">{{ $iotDevice->device_code }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Assign to Hive</li>
@endsection

@section('content')
@include('admin.iot-devices._styles')

<div class="alert-ademnea mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Pick the apiary where this device is being installed, then pick the hive it sits on. Hives that already
    have a <strong>{{ str_replace('_', ' ', $iotDevice->device_type) }}</strong> device are left out.
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><span class="badge bg-secondary me-2">1</span>Select Apiary</span>
                <span class="text-muted" style="font-size:0.72rem;font-weight:400;">{{ $apiaries->count() }} active</span>
            </div>
            <div class="list-group list-group-flush" style="max-height:480px;overflow-y:auto;">
                @forelse($apiaries as $apiary)
                    <button type="button"
                            class="list-group-item list-group-item-action apiary-option d-flex justify-content-between align-items-center"
                            hx-get="{{ route('admin.iot-devices.assign.hives', $iotDevice) }}?apiary_id={{ $apiary->id }}"
                            hx-target="#hive-step"
                            hx-swap="innerHTML"
                            hx-indicator="#hive-loading">
                        <span>
                            <span class="d-block fw-medium">{{ $apiary->name }}</span>
                            <span class="d-block apiary-meta">
                                <i class="bi bi-person me-1"></i>{{ $apiary->farmer_name === 'Unassigned' ? 'No farmer assigned' : 'Farmer: ' . $apiary->farmer_name }}
                                &nbsp;&bull;&nbsp; <i class="bi bi-globe-africa me-1"></i>{{ $apiary->country }}
                            </span>
                        </span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                @empty
                    <div class="p-4 text-muted text-center" style="font-size:0.82rem;">
                        <i class="bi bi-geo d-block mb-2" style="font-size:1.3rem;"></i>
                        No active apiaries yet.
                        <a href="{{ route('admin.apiaries.create') }}">Register an apiary</a> first.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><span class="badge bg-secondary me-2">2</span>Select Hive</span>
                <span id="hive-loading" class="htmx-indicator text-muted" style="font-size:0.72rem;font-weight:400;">
                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Loading hives…
                </span>
            </div>
            <div class="card-body" id="hive-step" aria-live="polite">
                <div class="text-muted text-center py-5" style="font-size:0.82rem;">
                    <i class="bi bi-arrow-left d-block mb-2" style="font-size:1.3rem;"></i>
                    Choose an apiary on the left to see its available hives.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('admin.iot-devices.show', $iotDevice) }}" class="btn btn-outline-forest">
        <i class="bi bi-arrow-left me-1"></i>Back to device
    </a>
</div>
@endsection

@push('styles')
<style>
    .apiary-option.active { background: var(--clr-forest-pale); border-color: var(--clr-forest-light); color: inherit; }
    .apiary-option.active .bi-chevron-right { color: var(--clr-forest) !important; }
    .apiary-meta { font-size: 0.78rem; color: #52635A; }
    /* Own rule so the spinner stays hidden even before htmx has loaded. */
    #hive-loading { opacity: 0; transition: opacity 0.15s; }
    #hive-loading.htmx-request { opacity: 1; }
    .hive-option { border: 1px solid var(--clr-border); border-radius: 8px; padding: 0.6rem 0.75rem 0.6rem 2.25rem; margin-bottom: 0.5rem; cursor: pointer; }
    .hive-option:hover { background: #FAFCFA; }
    .hive-option:has(input:checked) { background: var(--clr-forest-pale); border-color: var(--clr-forest-light); }
    .hive-option label { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var hiveStep = document.getElementById('hive-step');

        document.querySelectorAll('.apiary-option').forEach(function (option) {
            option.addEventListener('click', function () {
                document.querySelectorAll('.apiary-option').forEach(function (el) {
                    el.classList.remove('active');
                    el.removeAttribute('aria-current');
                });
                option.classList.add('active');
                option.setAttribute('aria-current', 'true');
            });
        });

        // htmx leaves the panel untouched on a failed request, so say what happened.
        function showLoadError() {
            hiveStep.innerHTML =
                '<div class="alert alert-danger mb-0" style="font-size:0.82rem;">' +
                '<i class="bi bi-exclamation-triangle me-1"></i>' +
                'The hives for this apiary could not be loaded. Select the apiary again to retry.' +
                '</div>';
        }
        document.body.addEventListener('htmx:responseError', showLoadError);
        document.body.addEventListener('htmx:sendError', showLoadError);
    })();
</script>
@endpush
