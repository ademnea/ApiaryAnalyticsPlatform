{{--
    Device health (IoT Condition Monitoring): live telemetry snapshot,
    7-day battery/signal trend, 24h submission rate and this device's anomaly incidents.
    Expects: $iotDevice, $health (IotDeviceHealthEvaluator), $anomalies,
    $openAnomaliesCount, $telemetryTrend, $trendChart, $submissionChart.
--}}
@php
    $telemetry = $iotDevice->telemetry;
    $batteryTone = match (true) {
        $health['battery_level'] === null => 'blue',
        $health['is_critical_battery'] => 'red',
        $health['is_low_battery'] => 'honey',
        default => 'green',
    };
    $signalTone = $health['signal_strength'] === null ? 'blue' : ($health['is_weak_signal'] ? 'honey' : 'green');
    $contactTone = match (true) { $health['never_reported'] => 'blue', $health['status'] === 'offline' => 'red', default => 'green' };
    [$statusBadge, $statusIcon, $statusLabel] = match (true) {
        $health['never_reported'] => ['badge-retired', 'bi-hourglass-split', 'Never reported'],
        $health['status'] === 'offline' => ['badge-offline', 'bi-wifi-off', 'Offline'],
        $health['status'] === 'warning' => ['badge-warning', 'bi-exclamation-triangle', 'Needs attention'],
        default => ['badge-active', 'bi-wifi', 'Online'],
    };
@endphp

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-activity me-1"></i>Device Health</span>
        @if($iotDevice->active_flag)
            <span class="badge {{ $statusBadge }}">
                <i class="bi {{ $statusIcon }} me-1"></i>{{ $statusLabel }}
            </span>
        @else
            <span class="badge badge-retired"><i class="bi bi-lock me-1"></i>Not monitored — access revoked</span>
        @endif
    </div>
    <div class="card-body">
        @if($health['never_reported'])
            <div class="alert-ademnea mb-3">
                <i class="bi bi-info-circle me-1"></i>
                This device has not sent any data yet. Battery, signal and storage appear here after its first report.
            </div>
        @endif

        <div class="row g-3">
            <div class="col-sm-6">
                <div class="stat-card h-100">
                    <div class="stat-icon {{ $batteryTone }}"><i class="bi bi-battery-half"></i></div>
                    <div>
                        <div class="stat-label mt-0">Battery</div>
                        <div class="stat-value" style="font-size:1.2rem;">{{ $health['battery_level'] !== null ? (int) $health['battery_level'] . '%' : 'No data' }}</div>
                        @if($health['battery_level'] !== null)
                            <div class="stat-label">{{ $health['is_critical_battery'] ? 'Critical — replace now' : ($health['is_low_battery'] ? 'Low — plan a replacement' : 'Healthy') }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="stat-card h-100">
                    <div class="stat-icon {{ $signalTone }}"><i class="bi bi-reception-4"></i></div>
                    <div>
                        <div class="stat-label mt-0">Network signal</div>
                        <div class="stat-value" style="font-size:1.2rem;">{{ $health['signal_strength'] !== null ? ($health['is_weak_signal'] ? 'Weak' : 'Good') : 'No data' }}</div>
                        @if($health['signal_strength'] !== null)
                            <div class="stat-label">{{ (int) $health['signal_strength'] }} dBm</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="stat-card h-100">
                    <div class="stat-icon blue"><i class="bi bi-hdd"></i></div>
                    <div>
                        <div class="stat-label mt-0">Storage used</div>
                        <div class="stat-value" style="font-size:1.2rem;">{{ $telemetry?->storage_usage !== null ? round($telemetry->storage_usage) . '%' : 'No data' }}</div>
                        @if($telemetry?->storage_usage !== null)
                            <div class="stat-label">of the device's memory</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="stat-card h-100">
                    <div class="stat-icon {{ $contactTone }}"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="stat-label mt-0">Last heard from</div>
                        <div class="stat-value" style="font-size:1.05rem;line-height:1.2;">{{ $health['last_contact']?->diffForHumans() ?? 'Never' }}</div>
                        @if($health['last_contact'])
                            <div class="stat-label">{{ $health['last_contact']->format('d M Y, H:i') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($telemetry)
            <dl class="row mb-0 mt-3 device-facts" style="font-size:0.82rem;">
                <dt class="col-sm-4">Firmware it reports</dt>
                <dd class="col-sm-8">{{ $telemetry->firmware_version ?: '—' }}</dd>
                <dt class="col-sm-4">Running since last restart</dt>
                <dd class="col-sm-8">{{ $telemetry->uptime_seconds !== null ? \Carbon\CarbonInterval::seconds($telemetry->uptime_seconds)->cascade()->forHumans(['short' => true, 'parts' => 2]) : '—' }}</dd>
                <dt class="col-sm-4">Restarts</dt>
                <dd class="col-sm-8">{{ $telemetry->reboot_count ?? '—' }}</dd>
                <dt class="col-sm-4">Successful sensor reads</dt>
                <dd class="col-sm-8">{{ $telemetry->sensor_read_success_rate !== null ? round($telemetry->sensor_read_success_rate, 1) . '%' : '—' }}</dd>
            </dl>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-graph-up me-1"></i>Battery &amp; Signal — Last 7 Days</div>
    <div class="card-body">
        @if($telemetryTrend->isEmpty())
            <div class="text-center text-muted py-4" style="font-size:0.82rem;">
                <i class="bi bi-hourglass-split d-block mb-2" style="font-size:1.5rem;"></i>
                No telemetry history recorded for this device in the last 7 days.
            </div>
        @else
            <canvas id="trendChart" height="160"></canvas>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bar-chart me-1"></i>Check-ins — Last 24 Hours</span>
        <span class="text-muted" style="font-size:0.72rem;font-weight:400;">
            {{ $submissionChart['received']->sum() }} received · about {{ $submissionChart['expected'] }} expected per hour
        </span>
    </div>
    <div class="card-body">
        @if($submissionChart['received']->sum() === 0)
            <div class="text-center text-muted py-4" style="font-size:0.82rem;">
                <i class="bi bi-broadcast d-block mb-2" style="font-size:1.5rem;"></i>
                This device has not checked in during the last 24 hours.
            </div>
        @else
            <canvas id="submissionChart" height="110"></canvas>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-shield-exclamation me-1"></i>Anomalies</span>
        <span style="font-size:0.72rem;font-weight:400;">
            @if($openAnomaliesCount > 0)
                <span class="badge badge-offline me-2">{{ $openAnomaliesCount }} unresolved</span>
            @endif
            <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'all', 'device_id' => $iotDevice->id]) }}">View all</a>
        </span>
    </div>

    @if($anomalies->isEmpty())
        <div class="card-body text-center text-muted py-4" style="font-size:0.82rem;">
            <i class="bi bi-shield-check d-block mb-2" style="font-size:1.5rem;color:var(--clr-forest-light);"></i>
            No anomalies recorded for this device.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.82rem;">
                <thead>
                    <tr><th>Anomaly</th><th>First seen</th><th class="text-center">Times seen</th><th>Status</th><th><span class="visually-hidden">Open</span></th></tr>
                </thead>
                <tbody>
                    @foreach($anomalies as $anomaly)
                        <tr>
                            <td>
                                <span class="badge {{ $anomaly->badgeClass() }}"><i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}</span>
                                <div class="text-muted text-capitalize" style="font-size:0.7rem;">{{ $anomaly->isDeviceIssue() ? 'Device issue' : $anomaly->sensor_type }}</div>
                            </td>
                            <td class="text-muted" title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">{{ $anomaly->detected_at->diffForHumans() }}</td>
                            <td class="text-center">{{ $anomaly->occurrences }}</td>
                            <td><span class="badge {{ $anomaly->statusBadgeClass() }} text-capitalize">{{ $anomaly->status() }}</span></td>
                            <td class="text-end"><a href="{{ route('admin.anomaly.anomalies.show', $anomaly) }}" class="text-decoration-none">Details <i class="bi bi-arrow-right"></i></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if($submissionChart['received']->sum() > 0)
    @push('scripts')
        <script>
            new Chart(document.getElementById('submissionChart'), {
                type: 'bar',
                data: {
                    labels: @json($submissionChart['labels']),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Check-ins received',
                            data: @json($submissionChart['received']),
                            backgroundColor: 'rgba(45,106,79,0.55)',
                            borderRadius: 3,
                        },
                        {
                            type: 'line',
                            label: 'Expected per hour',
                            data: @json(array_fill(0, 24, $submissionChart['expected'])),
                            borderColor: '#D4A017',
                            borderDash: [5, 4],
                            pointRadius: 0,
                            fill: false,
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: true,
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                },
            });
        </script>
    @endpush
@endif

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
