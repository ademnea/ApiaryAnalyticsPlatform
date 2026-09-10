@extends('layouts.app')

@section('title', 'Anomaly Dashboard')
@section('page-title', 'Anomaly Dashboard')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Anomaly Dashboard</li>
@endsection

@section('content')

    {{-- ---- KPI row ---- --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $unresolvedCount > 0 ? 'red' : 'green' }}">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $unresolvedCount }}</div>
                    <div class="stat-label">Unresolved {{ Str::plural('Anomaly', $unresolvedCount) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $offlineDevicesCount > 0 ? 'red' : 'green' }}">
                    <i class="bi bi-wifi-off"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $offlineDevicesCount }}</div>
                    <div class="stat-label">{{ Str::plural('Device', $offlineDevicesCount) }} Offline</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon honey">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $flaggedTodayCount }}</div>
                    <div class="stat-label">Flagged Today</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="bi bi-archive"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $totalTrackedCount }}</div>
                    <div class="stat-label">Total Tracked (All-Time)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- ---- Recent anomalies ---- --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ul me-1"></i>Recent Anomalies</span>
                    <span class="text-muted" style="font-size:0.72rem;font-weight:400;">Last 20</span>
                </div>

                @if($recentAnomalies->isEmpty())
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-shield-check d-block mb-2" style="font-size:1.8rem;color:var(--clr-forest-light);"></i>
                        No anomalies recorded yet — the fleet looks healthy.
                    </div>
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
                                @foreach($recentAnomalies as $anomaly)
                                    <tr>
                                        <td class="text-muted" title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">
                                            {{ $anomaly->detected_at->diffForHumans() }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $anomaly->badgeClass() }}">
                                                <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($anomaly->hive)
                                                {{ $anomaly->hive->display_name ?? $anomaly->hive->hive_code }}
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.anomaly.devices.show', $anomaly->device_id) }}">
                                                {{ $anomaly->device->device_code ?? '#' . $anomaly->device_id }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($anomaly->resolved)
                                                <span class="badge badge-active"><i class="bi bi-check-circle me-1"></i>Resolved</span>
                                            @else
                                                <span class="badge badge-warning"><i class="bi bi-exclamation-circle me-1"></i>Open</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ---- Breakdown by type ---- --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-pie-chart me-1"></i>Unresolved by Type</div>
                <div class="card-body">
                    @if($byType->isEmpty())
                        <p class="text-muted text-center py-4 mb-0" style="font-size:0.82rem;">
                            <i class="bi bi-shield-check d-block mb-2" style="font-size:1.5rem;color:var(--clr-forest-light);"></i>
                            Nothing outstanding.
                        </p>
                    @else
                        <canvas id="byTypeChart" height="220"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@if($byType->isNotEmpty())
    @push('scripts')
        <script>
            new Chart(document.getElementById('byTypeChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($byType->keys()->map(fn ($t) => ucwords(str_replace('_', ' ', $t)))),
                    datasets: [{
                        data: @json($byType->values()),
                        backgroundColor: ['#7F1D1D', '#D4A017', '#0057b8', '#2D6A4F', '#40916C', '#664D03'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                    maintainAspectRatio: true,
                },
            });
        </script>
    @endpush
@endif
