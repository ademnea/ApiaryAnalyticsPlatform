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

<div class="card">
    <div class="card-header"><i class="bi bi-lightbulb me-1" aria-hidden="true"></i>How to read this dashboard</div>
    <div class="card-body device-monitoring-hint">
        <p class="mb-2"><strong>Health</strong> combines recent device contact with battery and signal warnings.</p>
        <p class="mb-0"><strong>Battery level</strong> is reported by firmware, not calculated from voltage. Open Battery &amp; Power to review freshness and the seven-day trend.</p>
    </div>
</div>
