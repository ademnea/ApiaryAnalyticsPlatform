<div class="row g-3 mb-4">
    @foreach([
        ['Online', $kpis['online'].' ('.$kpis['online_rate'].'%)', 'bi-wifi', 'green'],
        ['Needs attention', $kpis['warning'], 'bi-exclamation-triangle', $kpis['warning'] > 0 ? 'honey' : 'green'],
        ['Offline', $kpis['offline'], 'bi-wifi-off', $kpis['offline'] > 0 ? 'red' : 'green'],
        ['Weak signal', $weakSignalDevices->count(), 'bi-reception-1', $weakSignalDevices->isNotEmpty() ? 'honey' : 'green'],
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

<div class="alert-ademnea mb-4" role="status">
    @if($kpis['total'] === 0)
        No active devices are registered yet.
    @else
        {{ $kpis['online_rate'] }}% of the active fleet is online.
        {{ $kpis['offline'] }} device{{ $kpis['offline'] === 1 ? '' : 's' }} have missed the configured
        {{ $batteryReadingStaleAfterMinutes }}-minute contact window; {{ $weakSignalDevices->count() }} device{{ $weakSignalDevices->count() === 1 ? '' : 's' }} have weak signal.
    @endif
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-broadcast me-1" aria-hidden="true"></i>Device connectivity</span>
        <form method="GET" action="{{ route('admin.devices.fleet') }}" class="d-flex align-items-center gap-2">
            <input type="hidden" name="section" value="connectivity">
            <label for="connectivity-health-filter" class="visually-hidden">Filter by health</label>
            <select id="connectivity-health-filter" name="health" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['online' => 'Online', 'warning' => 'Needs attention', 'offline' => 'Offline'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('health') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 device-monitoring-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Hive</th>
                    <th>Status</th>
                    <th>Signal</th>
                    <th>Last contact</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fleet as $device)
                    @php
                        $deviceHealth = $health[$device->id];
                        $healthClass = match ($deviceHealth['status']) {
                            'offline' => 'is-offline',
                            'warning' => 'is-warning',
                            default => 'is-online',
                        };
                    @endphp
                    <tr>
                        <td><a href="{{ route('admin.iot-devices.show', $device) }}" class="fw-semibold text-decoration-none">{{ $device->device_code }}</a></td>
                        <td>{{ $device->hive?->display_name ?? $device->hive?->hive_code ?? 'Unassigned' }}</td>
                        <td><span class="health-pill {{ $healthClass }}"><span class="dot"></span>{{ $deviceHealth['never_reported'] ? 'Never reported' : ucfirst($deviceHealth['status']) }}</span></td>
                        <td class="{{ $deviceHealth['is_weak_signal'] ? 'text-warning fw-semibold' : '' }}">
                            {{ $deviceHealth['signal_strength'] !== null ? number_format($deviceHealth['signal_strength'], 0).' dBm' : '—' }}
                            @if($deviceHealth['is_weak_signal'])
                                <div class="device-monitoring-hint">Below configured signal threshold</div>
                            @endif
                        </td>
                        <td class="text-muted" @if($deviceHealth['last_contact']) title="{{ $deviceHealth['last_contact']->format('Y-m-d H:i:s') }}" @endif>
                            {{ $deviceHealth['last_contact']?->diffForHumans() ?? 'Never' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No devices match this health filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($fleet->hasPages())
        <div class="card-footer">{{ $fleet->links() }}</div>
    @endif
</div>
