<div class="row g-3 mb-4">
    @foreach([
        ['Battery reporting', $kpis['battery_reporting'].' / '.$kpis['total'], 'bi-battery-half', 'blue'],
        ['Low or critical', $kpis['low_battery'], 'bi-battery-quarter', $kpis['low_battery'] > 0 ? 'honey' : 'green'],
        ['Stale readings', $kpis['stale_battery'], 'bi-clock-history', $kpis['stale_battery'] > 0 ? 'honey' : 'green'],
        ['Average level', $kpis['average_battery_level'] !== null ? number_format($kpis['average_battery_level'], 1).'%' : '—', 'bi-percent', 'green'],
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
        <p class="device-monitoring-hint mt-2 mb-0">Battery percentage is reported by device firmware. Voltage uses a separate chart scale; current is shown as reported because its unit is not defined.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-battery-charging me-1" aria-hidden="true"></i>Battery readings</span>
        <form method="GET" action="{{ route('admin.devices.fleet') }}" class="d-flex align-items-center gap-2">
            <input type="hidden" name="section" value="battery">
            <label for="battery-filter" class="visually-hidden">Filter battery readings</label>
            <select id="battery-filter" name="battery" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All active devices</option>
                <option value="low" @selected(request('battery') === 'low')>Low or critical</option>
                <option value="stale" @selected(request('battery') === 'stale')>Stale readings</option>
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
                    <th>Battery level</th>
                    <th>Voltage</th>
                    <th>Current</th>
                    <th>Freshness</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fleet as $device)
                    @php
                        $deviceHealth = $health[$device->id];
                        $batteryReading = $batteryReadings[$device->id];
                        $batteryBadge = match (true) {
                            $deviceHealth['is_critical_battery'] => ['Critical', 'badge-offline'],
                            $deviceHealth['is_low_battery'] => ['Low', 'badge-warning'],
                            $deviceHealth['battery_level'] !== null => ['Normal', 'badge-active'],
                            default => ['No level', 'bg-secondary'],
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="fw-semibold text-decoration-none">{{ $device->device_code }}</a>
                            @if($device->mac_address)
                                <div class="device-monitoring-hint">{{ $device->mac_address }}</div>
                            @endif
                        </td>
                        <td>{{ $device->hive?->display_name ?? $device->hive?->hive_code ?? 'Unassigned' }}</td>
                        <td><span class="badge {{ $batteryBadge[1] }}">{{ $batteryBadge[0] }}</span></td>
                        <td>{{ $deviceHealth['battery_level'] !== null ? number_format($deviceHealth['battery_level'], 1).'%' : '—' }}</td>
                        <td>{{ $device->telemetry?->battery_voltage !== null ? number_format($device->telemetry->battery_voltage, 2).' V' : '—' }}</td>
                        <td>{{ $device->telemetry?->battery_current !== null ? number_format($device->telemetry->battery_current, 2) : '—' }}</td>
                        <td>
                            @if($batteryReading['recorded_at'] === null)
                                <span class="badge bg-secondary">No reading</span>
                            @elseif($batteryReading['is_stale'])
                                <span class="badge badge-warning">Stale</span>
                                <div class="device-monitoring-hint" title="{{ $batteryReading['recorded_at']->format('Y-m-d H:i:s T') }}">{{ $batteryReading['recorded_at']->diffForHumans() }}</div>
                            @else
                                <span class="badge badge-active">Fresh</span>
                                <div class="device-monitoring-hint" title="{{ $batteryReading['recorded_at']->format('Y-m-d H:i:s T') }}">{{ $batteryReading['recorded_at']->diffForHumans() }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No devices match this battery filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($fleet->hasPages())
        <div class="card-footer">{{ $fleet->links() }}</div>
    @endif
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
