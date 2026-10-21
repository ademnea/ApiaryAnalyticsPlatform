<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon {{ $kpis['open_issues'] > 0 ? 'red' : 'green' }}"><i class="bi bi-shield-exclamation" aria-hidden="true"></i></div>
            <div>
                <div class="stat-value device-monitoring-tabular">{{ $kpis['open_issues'] }}</div>
                <div class="stat-label">Open device issues</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon {{ $kpis['offline'] > 0 ? 'honey' : 'green' }}"><i class="bi bi-wifi-off" aria-hidden="true"></i></div>
            <div>
                <div class="stat-value device-monitoring-tabular">{{ $kpis['offline'] }}</div>
                <div class="stat-label">Offline devices</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-shield-exclamation me-1" aria-hidden="true"></i>Latest open device issues</span>
        <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'device', 'status' => 'unresolved']) }}" class="btn btn-sm btn-outline-forest">
            View all issues
        </a>
    </div>
    @if($latestIssues->isEmpty())
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-shield-check d-block mb-2" style="font-size:1.7rem;color:var(--clr-forest-light);" aria-hidden="true"></i>
            No open device issues. Devices are being monitored for battery, signal, storage, restart, and connectivity problems.
        </div>
    @else
        <div class="device-attention-list">
            @foreach($latestIssues as $issue)
                <a href="{{ route('admin.anomaly.anomalies.show', $issue) }}" class="device-attention-row">
                    <span class="device-attention-dot {{ $issue->severity() === 'critical' ? 'critical' : '' }}" aria-hidden="true"></span>
                    <span class="flex-grow-1 min-w-0">
                        <span class="fw-semibold d-block">{{ $issue->device->device_code ?? '#'.$issue->device_id }} · {{ $issue->label() }}</span>
                        <span class="device-monitoring-hint">{{ $issue->detected_at->diffForHumans() }} · {{ $issue->occurrences }} occurrence{{ $issue->occurrences === 1 ? '' : 's' }}</span>
                    </span>
                    <span class="badge {{ $issue->statusBadgeClass() }} text-capitalize">{{ $issue->status() }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
