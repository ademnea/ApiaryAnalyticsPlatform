@extends('layouts.app')

@section('title', $device->device_code)
@section('page-title', $device->device_code)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.anomaly.dashboard') }}">Anomaly Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $device->device_code }}</li>
@endsection

@section('content')

    @php
        $telemetry = $device->telemetry;
        // Cosmetic tiering only, mirrors the seeded rule defaults
        // (low_battery_pct=20, critical_battery_pct=5, weak_signal_rssi_dbm=-85,
        // storage_full_pct=90) — the actual thresholds enforced by
        // DeviceTelemetryRuleEvaluator are per-hive-overridable via
        // AlertThreshold; this is only for the at-a-glance icon color.
        $batteryTier = fn ($v) => $v === null ? 'blue' : ($v <= 5 ? 'red' : ($v <= 20 ? 'honey' : 'green'));
        $signalTier = fn ($v) => $v === null ? 'blue' : ($v <= -85 ? 'red' : 'green');
        $storageTier = fn ($v) => $v === null ? 'blue' : ($v >= 90 ? 'red' : 'green');
    @endphp

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div class="text-muted" style="font-size:0.82rem;">
            <i class="bi bi-hexagon me-1"></i>
            @if($device->hive)
                Assigned to <a href="{{ route('admin.hives.show', $device->hive) }}">{{ $device->hive->display_name ?? $device->hive->hive_code }}</a>
            @else
                <span class="text-muted">Not currently assigned to a hive</span>
            @endif
        </div>
        <a href="{{ route('admin.iot-devices.show', $device) }}" class="btn btn-sm btn-outline-forest">
            <i class="bi bi-cpu me-1"></i>Device Registry Record
        </a>
    </div>

    {{-- ---- Current telemetry snapshot ---- --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $batteryTier($telemetry?->battery_level) }}"><i class="bi bi-battery-half"></i></div>
                <div>
                    <div class="stat-value">{{ $telemetry?->battery_level !== null ? $telemetry->battery_level . '%' : '—' }}</div>
                    <div class="stat-label">Battery</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $signalTier($telemetry?->signal_strength) }}"><i class="bi bi-reception-4"></i></div>
                <div>
                    <div class="stat-value">{{ $telemetry?->signal_strength !== null ? $telemetry->signal_strength . ' dBm' : '—' }}</div>
                    <div class="stat-label">Signal</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $storageTier($telemetry?->storage_usage) }}"><i class="bi bi-hdd"></i></div>
                <div>
                    <div class="stat-value">{{ $telemetry?->storage_usage !== null ? $telemetry->storage_usage . '%' : '—' }}</div>
                    <div class="stat-label">Storage Used</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-value" style="font-size:1.1rem;">
                        {{ $telemetry?->last_heartbeat_at?->diffForHumans() ?? 'Never' }}
                    </div>
                    <div class="stat-label">Last Heartbeat</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- ---- Battery / signal trend ---- --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-graph-up me-1"></i>Battery &amp; Signal Trend — 7 Days</div>
                <div class="card-body">
                    @if($telemetryTrend->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-hourglass-split d-block mb-2" style="font-size:1.5rem;"></i>
                            No telemetry history recorded yet for this device.
                        </div>
                    @else
                        <canvas id="trendChart" height="200"></canvas>
                    @endif
                </div>
            </div>
        </div>

        {{-- ---- Recent anomalies ---- --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-shield-exclamation me-1"></i>Recent Anomalies</div>

                @if($anomalies->isEmpty())
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-shield-check d-block mb-2" style="font-size:1.5rem;color:var(--clr-forest-light);"></i>
                        No anomalies recorded for this device.
                    </div>
                @else
                    <div class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;">
                        @foreach($anomalies as $anomaly)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="badge {{ $anomaly->badgeClass() }}">
                                        <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
                                    </span>
                                    @if($anomaly->resolved)
                                        <span class="badge badge-active">Resolved</span>
                                    @else
                                        <span class="badge badge-warning">Open</span>
                                    @endif
                                </div>
                                <div class="text-muted mt-1" style="font-size:0.72rem;" title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">
                                    {{ $anomaly->detected_at->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@if($telemetryTrend->isNotEmpty())
    @push('scripts')
        <script>
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: @json($trendChart['labels']),
                    datasets: [
                        {
                            label: 'Battery (%)',
                            data: @json($trendChart['battery']),
                            borderColor: '#2D6A4F',
                            backgroundColor: 'rgba(45,106,79,0.1)',
                            yAxisID: 'y',
                            tension: 0.3,
                        },
                        {
                            label: 'Signal (dBm)',
                            data: @json($trendChart['signal']),
                            borderColor: '#D4A017',
                            backgroundColor: 'rgba(212,160,23,0.1)',
                            yAxisID: 'y1',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { type: 'linear', position: 'left', min: 0, max: 100, title: { display: true, text: 'Battery %' } },
                        y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Signal dBm' } },
                    },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                },
            });
        </script>
    @endpush
@endif
