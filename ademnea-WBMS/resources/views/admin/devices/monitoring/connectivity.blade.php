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

<div class="card mt-4" id="connectivity-readings">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span><i class="bi bi-table me-1" aria-hidden="true"></i>Connectivity readings</span>
            <div class="device-monitoring-hint">
                Device telemetry from {{ $connectivityHistoryFrom->format('M j, Y H:i') }} to {{ $connectivityHistoryTo->format('M j, Y H:i') }} EAT.
            </div>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="{{ route('admin.devices.fleet.readings.export', ['stream' => 'connectivity'] + request()->only(['connectivity_range', 'connectivity_device', 'connectivity_sort'])) }}"
               class="btn btn-sm btn-outline-forest">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Export CSV
            </a>
            <form method="GET" action="{{ route('admin.devices.fleet') }}#connectivity-readings" class="d-flex align-items-center flex-wrap gap-2">
                <input type="hidden" name="section" value="connectivity">
                <label for="connectivity-history-range" class="visually-hidden">Connectivity reading time range</label>
                <select id="connectivity-history-range" name="connectivity_range" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    @foreach(['24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days'] as $value => $label)
                        <option value="{{ $value }}" @selected($connectivityHistoryRange === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label for="connectivity-history-device" class="visually-hidden">Filter connectivity readings by device</label>
                <select id="connectivity-history-device" name="connectivity_device" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    <option value="">All devices</option>
                    @foreach($connectivityHistoryDevices as $deviceOption)
                        <option value="{{ $deviceOption->id }}" @selected((string) $connectivityHistoryDevice === (string) $deviceOption->id)>{{ $deviceOption->device_code }}</option>
                    @endforeach
                </select>
                <label for="connectivity-history-sort" class="visually-hidden">Sort connectivity readings</label>
                <select id="connectivity-history-sort" name="connectivity_sort" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    <option value="recorded" @selected($connectivityHistorySort === 'recorded')>Newest by device time</option>
                    <option value="received" @selected($connectivityHistorySort === 'received')>Newest by received time</option>
                </select>
                <label for="connectivity-history-per-page" class="visually-hidden">Connectivity rows per page</label>
                <select id="connectivity-history-per-page" name="connectivity_per_page" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    @foreach([25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($connectivityHistoryPerPage === $option)>{{ $option }} rows</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-sm btn-outline-forest">Apply</button></noscript>
            </form>
        </div>
    </div>

    @if($connectivityHistory->isEmpty())
        <div class="text-center text-muted py-5 px-3" role="status">
            <i class="bi bi-inbox d-block mb-2" aria-hidden="true"></i>
            <p class="fw-medium text-body mb-1">No connectivity readings to list</p>
            <p class="mb-0 device-monitoring-hint">Signal, uptime, sensor-read success, and device health reports appear here when devices check in.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 device-monitoring-table">
                <caption class="visually-hidden">Connectivity reports from devices, newest {{ $connectivityHistorySort === 'received' ? 'received' : 'recorded' }} first</caption>
                <thead>
                    <tr>
                        <th scope="col">Sent by device</th>
                        <th scope="col">Received by server</th>
                        <th scope="col">Device</th>
                        <th scope="col">Hive</th>
                        <th scope="col">Signal</th>
                        <th scope="col">Uptime</th>
                        <th scope="col">Sensor-read success</th>
                        <th scope="col">Reboots</th>
                        <th scope="col">CPU / storage</th>
                        <th scope="col">Errors</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($connectivityHistory as $reading)
                        @php($readingDevice = $reading->device)
                        <tr>
                            <td class="text-nowrap">
                                <time datetime="{{ $reading->recorded_at->toIso8601String() }}" title="{{ $reading->recorded_at->format('Y-m-d H:i:s T') }}">{{ $reading->recorded_at->format('M j, Y H:i') }}</time>
                                <div class="device-monitoring-hint">{{ $reading->recorded_at->diffForHumans() }}</div>
                            </td>
                            <td class="text-nowrap">
                                @if($reading->created_at)
                                    <time datetime="{{ $reading->created_at->toIso8601String() }}" title="{{ $reading->created_at->format('Y-m-d H:i:s T') }}">{{ $reading->created_at->format('M j, Y H:i') }}</time>
                                    <div class="device-monitoring-hint">{{ $reading->created_at->diffForHumans() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($readingDevice)
                                    <a href="{{ route('admin.iot-devices.show', $readingDevice) }}" class="fw-semibold text-decoration-none">{{ $readingDevice->device_code }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $readingDevice?->hive?->display_name ?? $readingDevice?->hive?->hive_code ?? 'Unassigned' }}</td>
                            <td>{{ $reading->signal_strength !== null ? number_format($reading->signal_strength, 0).' dBm' : '—' }}</td>
                            <td>{{ $reading->uptime_seconds !== null ? number_format($reading->uptime_seconds).' s' : '—' }}</td>
                            <td>{{ $reading->sensor_read_success_rate !== null ? number_format($reading->sensor_read_success_rate * 100, 1).'%' : '—' }}</td>
                            <td>{{ $reading->reboot_count !== null ? number_format($reading->reboot_count) : '—' }}</td>
                            <td>
                                {{ $reading->cpu_usage !== null ? number_format($reading->cpu_usage, 1).'%' : '—' }}
                                /
                                {{ $reading->storage_usage !== null ? number_format($reading->storage_usage, 1).'%' : '—' }}
                            </td>
                            <td>{{ $reading->error_codes ? json_encode($reading->error_codes, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($connectivityHistory->hasPages())
            <div class="card-footer">{{ $connectivityHistory->fragment('connectivity-readings')->links() }}</div>
        @endif
    @endif
</div>
