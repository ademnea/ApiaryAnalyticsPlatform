@extends('layouts.app')

@section('title', 'Anomaly Analytics')
@section('page-title', 'Anomaly Analytics')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.anomaly.dashboard') }}">Anomaly Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Analytics</li>
@endsection

@push('styles')
    <style>
        .heatmap-table { border-collapse: separate; border-spacing: 3px; }
        .heatmap-table th { border: none; font-size: 0.68rem; padding: 0.25rem 0.35rem; text-align: center; white-space: nowrap; }
        .heatmap-table th:first-child, .heatmap-table td:first-child { text-align: left; white-space: nowrap; padding-right: 0.75rem; }
        .heatmap-cell {
            width: 34px; height: 30px; min-width: 34px;
            text-align: center; vertical-align: middle;
            border-radius: 5px; font-size: 0.72rem; font-weight: 600;
            color: #fff; border: none !important;
        }
        .heatmap-cell.empty { background: var(--clr-border) !important; color: transparent; }
    </style>
@endpush

@section('content')

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-grid-3x3-gap me-1"></i>Anomaly Heatmap — Last {{ $days }} Days</span>
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('admin.anomaly.analytics', ['days' => 7]) }}"
                   class="btn {{ $days === 7 ? 'btn-primary' : 'btn-outline-forest' }}">7 Days</a>
                <a href="{{ route('admin.anomaly.analytics', ['days' => 30]) }}"
                   class="btn {{ $days === 30 ? 'btn-primary' : 'btn-outline-forest' }}">30 Days</a>
            </div>
        </div>
        <div class="card-body">
            @if($hives->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-shield-check d-block mb-2" style="font-size:1.8rem;color:var(--clr-forest-light);"></i>
                    No hive-linked anomalies in this window.
                </div>
            @else
                <div class="table-responsive">
                    <table class="heatmap-table">
                        <thead>
                            <tr>
                                <th>Hive</th>
                                @foreach($dates as $date)
                                    <th>{{ \Illuminate\Support\Carbon::parse($date)->format($days > 7 ? 'j' : 'D j') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hives as $hive)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.hives.show', $hive) }}">
                                            {{ $hive->display_name ?? $hive->hive_code }}
                                        </a>
                                    </td>
                                    @foreach($dates as $date)
                                        @php
                                            $count = $byHiveAndDate->get($hive->id)?->get($date) ?? 0;
                                            $intensity = $count > 0 ? 0.25 + (0.75 * min($count, $maxDailyCount) / $maxDailyCount) : 0;
                                        @endphp
                                        <td class="heatmap-cell {{ $count === 0 ? 'empty' : '' }}"
                                            style="{{ $count > 0 ? 'background: rgba(27,67,50,' . $intensity . ')' : '' }}"
                                            title="{{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y') }}: {{ $count }} anomal{{ $count === 1 ? 'y' : 'ies' }}">
                                            {{ $count > 0 ? $count : '' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem;">
                    <i class="bi bi-info-circle me-1"></i>Darker cells indicate more anomalies detected that day. Hover a cell for the exact count.
                </p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-list-ul me-1"></i>All Anomalies in Window</div>

        @if($anomalies->isEmpty())
            <div class="card-body text-center text-muted py-4">No anomalies in this window.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Detected</th>
                            <th>Anomaly</th>
                            <th>Hive</th>
                            <th>Device</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($anomalies->take(50) as $anomaly)
                            <tr>
                                <td class="text-muted" title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">
                                    {{ $anomaly->detected_at->diffForHumans() }}
                                </td>
                                <td>
                                    <span class="badge {{ $anomaly->badgeClass() }}">
                                        <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
                                    </span>
                                </td>
                                <td>{{ $anomaly->hive?->display_name ?? $anomaly->hive?->hive_code ?? '—' }}</td>
                                <td><a href="{{ route('admin.anomaly.devices.show', $anomaly->device_id) }}">{{ $anomaly->device->device_code ?? '#' . $anomaly->device_id }}</a></td>
                                <td>
                                    @if($anomaly->resolved)
                                        <span class="badge badge-active">Resolved</span>
                                    @else
                                        <span class="badge badge-warning">Open</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($anomalies->count() > 50)
                <div class="card-body text-muted text-center py-2" style="font-size:0.75rem;">
                    Showing the most recent 50 of {{ $anomalies->count() }}.
                </div>
            @endif
        @endif
    </div>

@endsection
