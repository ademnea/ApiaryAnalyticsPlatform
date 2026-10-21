@extends('layouts.app')

@php
    $monitoringTabs = [
        'overview' => ['Overview', 'bi-grid-1x2'],
        'battery' => ['Battery & Power', 'bi-battery-charging'],
        'connectivity' => ['Connectivity', 'bi-wifi'],
        'issues' => ['Issues', 'bi-shield-exclamation'],
    ];
@endphp

@section('title', 'Device Monitoring')
@section('page-title', 'Device Monitoring')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Device Monitoring</li>
@endsection

@push('styles')
    <style>
        .device-monitoring-tabs { display: flex; gap: 0.4rem; overflow-x: auto; padding-bottom: 0.25rem; }
        .device-monitoring-tab {
            display: inline-flex; align-items: center; gap: 0.4rem; white-space: nowrap;
            padding: 0.45rem 0.85rem; border: 1px solid var(--clr-border); border-radius: 999px;
            background: var(--clr-card); color: var(--clr-text); font-size: 0.8rem; text-decoration: none;
        }
        .device-monitoring-tab:hover { border-color: var(--clr-forest-light); color: var(--clr-forest); }
        .device-monitoring-tab.active { border-color: var(--clr-forest-mid); background: var(--clr-forest-mid); color: #fff; }
        .device-monitoring-tab:focus-visible { outline: 2px solid var(--clr-honey); outline-offset: 2px; }
        .device-monitoring-hint { font-size: 0.78rem; color: var(--clr-muted); }
        .device-monitoring-table th { white-space: nowrap; }
        .device-attention-list { max-height: 320px; overflow-y: auto; }
        .device-attention-row {
            display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 0.9rem;
            border-bottom: 1px solid var(--clr-border); color: inherit; text-decoration: none;
        }
        .device-attention-row:last-child { border-bottom: 0; }
        .device-attention-row:hover { background: var(--clr-canvas); }
        .device-attention-dot { width: 8px; height: 8px; flex: 0 0 auto; border-radius: 50%; background: #D4A017; }
        .device-attention-dot.critical { background: #b30000; }
        .device-monitoring-tabular { font-variant-numeric: tabular-nums; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h2 class="h5 mb-1">Monitor the full device lifecycle</h2>
            <p class="device-monitoring-hint mb-0">
                Fleet status updated {{ $fleetGeneratedAt->diffForHumans() }}.
                Battery freshness uses a {{ $batteryReadingStaleAfterMinutes }}-minute silence threshold.
            </p>
        </div>
        <a href="{{ route('admin.iot-devices.index') }}" class="btn btn-sm btn-outline-forest">
            <i class="bi bi-cpu me-1" aria-hidden="true"></i>Device Registry
        </a>
    </div>

    <nav class="device-monitoring-tabs mb-3" aria-label="Device monitoring sections">
        @foreach($monitoringTabs as $key => [$label, $icon])
            <a href="{{ route('admin.devices.fleet', ['section' => $key]) }}"
               class="device-monitoring-tab {{ $section === $key ? 'active' : '' }}"
               @if($section === $key) aria-current="page" @endif>
                <i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $label }}
            </a>
        @endforeach
    </nav>

    @include('admin.devices.monitoring.'.$section)
@endsection
