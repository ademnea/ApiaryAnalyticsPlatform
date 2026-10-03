@extends('layouts.app')

@section('title', 'All Anomalies')
@section('page-title', 'All Anomalies')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.anomaly.dashboard') }}">Anomaly Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">All Anomalies</li>
@endsection

@section('content')

    @include('admin.anomaly._subnav')

    {{-- ---- Category tabs ---- --}}
    <ul class="nav nav-tabs mb-3">
        @foreach($categories as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $filters['category'] === $key ? 'active' : '' }}"
                   href="{{ route('admin.anomaly.anomalies.index', array_filter(['category' => $key, 'status' => $filters['status']])) }}">
                    @if($key === 'hive')<i class="bi bi-hexagon me-1"></i>@elseif($key === 'device')<i class="bi bi-cpu me-1"></i>@endif
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- ---- Filters ---- --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="category" value="{{ $filters['category'] }}">
                <div class="col-6 col-md-2">
                    <label for="filter-status" class="form-label mb-1" style="font-size:0.8rem;">Status</label>
                    <select name="status" id="filter-status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter-severity" class="form-label mb-1" style="font-size:0.8rem;">Severity</label>
                    <select name="severity" id="filter-severity" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($severities as $value => $label)
                            <option value="{{ $value }}" @selected($filters['severity'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter-type" class="form-label mb-1" style="font-size:0.8rem;">Type</label>
                    <select name="type" id="filter-type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ \App\Models\SensorAnomaly::labelFor($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter-hive_id" class="form-label mb-1" style="font-size:0.8rem;">Hive</label>
                    <select name="hive_id" id="filter-hive_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($hives as $hive)
                            <option value="{{ $hive->id }}" @selected($filters['hive_id'] === $hive->id)>{{ $hive->display_name ?? $hive->hive_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter-device_id" class="form-label mb-1" style="font-size:0.8rem;">Device</label>
                    <select name="device_id" id="filter-device_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" @selected($filters['device_id'] === $device->id)>{{ $device->device_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 text-end">
                    <a href="{{ route('admin.anomaly.anomalies.index', ['category' => $filters['category']]) }}" class="btn btn-sm btn-outline-forest">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter-from" class="form-label mb-1" style="font-size:0.8rem;">Detected from</label>
                    <input type="date" name="from" id="filter-from" class="form-control form-control-sm" value="{{ $filters['from']?->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter-to" class="form-label mb-1" style="font-size:0.8rem;">Detected to</label>
                    <input type="date" name="to" id="filter-to" class="form-control form-control-sm" value="{{ $filters['to']?->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    {{-- ---- Results ---- --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-1"></i>{{ $categories[$filters['category']] }}</span>
            <span class="text-muted" style="font-size:0.72rem;font-weight:400;">{{ $anomalies->total() }} incident(s)</span>
        </div>

        @if($anomalies->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-shield-check d-block mb-2" style="font-size:1.8rem;color:var(--clr-forest-light);"></i>
                No anomalies match these filters.
            </div>
        @else
            @include('admin.anomaly._incident-table', ['caption' => $categories[$filters['category']].', unresolved first, then most recently active'])
        @endif

        @if($anomalies->hasPages())
            <div class="card-footer bg-white">{{ $anomalies->links() }}</div>
        @endif
    </div>

@endsection
