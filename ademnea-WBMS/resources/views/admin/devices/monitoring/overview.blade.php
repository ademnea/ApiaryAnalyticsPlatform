<div class="row g-3 mb-4">
    @foreach([
        ['Active devices', $kpis['total'], 'bi-cpu', 'blue'],
        ['Online', $kpis['online'].' ('.$kpis['online_rate'].'%)', 'bi-check-circle', 'green'],
        ['Need attention', $kpis['warning'] + $kpis['offline'], 'bi-exclamation-triangle', ($kpis['warning'] + $kpis['offline']) > 0 ? 'honey' : 'green'],
        ['Open device issues', $kpis['open_issues'], 'bi-shield-exclamation', $kpis['open_issues'] > 0 ? 'red' : 'green'],
    ] as [$label, $value, $icon, $tone])
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon {{ $tone }}"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value device-monitoring-tabular">{{ $value }}</div>
                    <div class="stat-label">{{ $label }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="{{ route('admin.devices.fleet', ['section' => 'battery']) }}" class="card h-100 text-decoration-none text-reset">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stat-icon {{ $kpis['low_battery'] > 0 || $kpis['stale_battery'] > 0 ? 'honey' : 'green' }}">
                        <i class="bi bi-battery-half" aria-hidden="true"></i>
                    </div>
                    <i class="bi bi-arrow-up-right text-muted" aria-hidden="true"></i>
                </div>
                <h2 class="h6 mt-3 mb-1">Battery &amp; Power</h2>
                <p class="device-monitoring-hint mb-0">
                    {{ $kpis['battery_reporting'] }} devices reporting;
                    {{ $kpis['low_battery'] }} low and {{ $kpis['stale_battery'] }} stale.
                    @if($kpis['average_battery_level'] !== null)
                        Fleet average is {{ number_format($kpis['average_battery_level'], 1) }}%.
                    @else
                        No battery percentage reported yet.
                    @endif
                </p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.devices.fleet', ['section' => 'connectivity']) }}" class="card h-100 text-decoration-none text-reset">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stat-icon {{ $kpis['offline'] > 0 || $weakSignalDevices->isNotEmpty() ? 'honey' : 'green' }}">
                        <i class="bi bi-wifi" aria-hidden="true"></i>
                    </div>
                    <i class="bi bi-arrow-up-right text-muted" aria-hidden="true"></i>
                </div>
                <h2 class="h6 mt-3 mb-1">Connectivity</h2>
                <p class="device-monitoring-hint mb-0">
                    {{ $kpis['offline'] }} offline and {{ $weakSignalDevices->count() }} with weak signal.
                    {{ $kpis['online_rate'] }}% of active devices are currently online.
                </p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.devices.fleet', ['section' => 'issues']) }}" class="card h-100 text-decoration-none text-reset">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stat-icon {{ $kpis['open_issues'] > 0 ? 'red' : 'green' }}">
                        <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
                    </div>
                    <i class="bi bi-arrow-up-right text-muted" aria-hidden="true"></i>
                </div>
                <h2 class="h6 mt-3 mb-1">Device Issues</h2>
                <p class="device-monitoring-hint mb-0">
                    {{ $kpis['open_issues'] === 0 ? 'No open device issues need action.' : $kpis['open_issues'].' open issue(s) need review.' }}
                    Review incidents and their latest reported evidence.
                </p>
            </div>
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-graph-up me-1" aria-hidden="true"></i>Fleet battery trend · Last 7 days</span>
        @if($batteryTrendDays >= 2)
            <span class="device-monitoring-hint">{{ number_format($batteryTrendReadingsCount) }} readings across {{ $batteryTrendDays }} days</span>
        @endif
    </div>
    <div class="card-body">
        @if($batteryTrendChange !== null)
            <div class="alert-ademnea mb-3" role="status">
                @if($batteryTrendChange < 0)
                    The fleet average battery level fell by {{ number_format(abs($batteryTrendChange), 1) }} percentage points
                @elseif($batteryTrendChange > 0)
                    The fleet average battery level rose by {{ number_format($batteryTrendChange, 1) }} percentage points
                @else
                    The fleet average battery level was unchanged
                @endif
                between {{ \Illuminate\Support\Carbon::parse($batteryTrendFirstDay)->format('M j') }}
                and {{ \Illuminate\Support\Carbon::parse($batteryTrendLastDay)->format('M j') }}.
                This compares daily averages across reported readings, not individual-device battery life.
            </div>
        @endif

        @if($batteryTrendDays >= 2)
            <canvas id="batteryTrendChart" height="110" role="img" aria-label="Daily fleet average battery level and voltage over the last seven days"></canvas>
        @else
            <div class="text-center text-muted py-3 device-monitoring-hint">
                <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                Not enough history for a trend yet. Readings are recorded as devices check in.
                @if($batteryTrendReadingsCount > 0)
                    {{ number_format($batteryTrendReadingsCount) }} reading{{ $batteryTrendReadingsCount === 1 ? '' : 's' }} recorded so far.
                @endif
            </div>
        @endif
        <p class="device-monitoring-hint mt-2 mb-0">
            Battery percentage is reported by device firmware. Voltage uses a separate chart scale; current is shown in readings without a unit conversion.
            <a href="{{ route('admin.devices.fleet', ['section' => 'battery']) }}">View detailed battery readings</a>.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-lightbulb me-1" aria-hidden="true"></i>How to read this dashboard</div>
    <div class="card-body device-monitoring-hint">
        <p class="mb-2"><strong>Health</strong> combines recent device contact with battery and signal warnings.</p>
        <p class="mb-0"><strong>Battery level</strong> is reported by firmware, not calculated from voltage. Use Battery &amp; Power to review individual readings.</p>
    </div>
</div>

@if($batteryTrendDays >= 2)
    @push('scripts')
        <script>
            new Chart(document.getElementById('batteryTrendChart'), {
                type: 'line',
                data: {
                    labels: @json($batteryTrendChart['labels']),
                    datasets: [
                        {
                            label: 'Battery level (%)',
                            data: @json($batteryTrendChart['battery']),
                            borderColor: '#2D6A4F',
                            backgroundColor: 'rgba(45,106,79,0.1)',
                            yAxisID: 'battery',
                            tension: 0.3,
                            spanGaps: false,
                        },
                        {
                            label: 'Voltage (V)',
                            data: @json($batteryTrendChart['voltage']),
                            borderColor: '#D4A017',
                            backgroundColor: 'rgba(212,160,23,0.1)',
                            yAxisID: 'voltage',
                            tension: 0.3,
                            spanGaps: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        battery: {
                            type: 'linear',
                            position: 'left',
                            min: 0,
                            max: 100,
                            title: { display: true, text: 'Battery level (%)' },
                        },
                        voltage: {
                            type: 'linear',
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Voltage (V)' },
                        },
                    },
                },
            });
        </script>
    @endpush
@endif
