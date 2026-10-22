<div class="card" id="battery-readings">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span><i class="bi bi-table me-1" aria-hidden="true"></i>Battery readings</span>
            <div class="device-monitoring-hint">
                Individual device reports from {{ $batteryHistoryFrom->format('M j, Y H:i') }} to {{ $batteryHistoryTo->format('M j, Y H:i') }} EAT.
            </div>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="{{ route('admin.devices.fleet.readings.export', ['stream' => 'battery'] + request()->only(['battery_range', 'battery_device', 'battery_sort'])) }}"
               class="btn btn-sm btn-outline-forest">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Export CSV
            </a>
            <form method="GET" action="{{ route('admin.devices.fleet') }}#battery-readings" class="d-flex align-items-center flex-wrap gap-2">
                <input type="hidden" name="section" value="battery">
                <label for="battery-history-range" class="visually-hidden">Battery reading time range</label>
                <select id="battery-history-range" name="battery_range" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    @foreach(['24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days'] as $value => $label)
                        <option value="{{ $value }}" @selected($batteryHistoryRange === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label for="battery-history-device" class="visually-hidden">Filter readings by device</label>
                <select id="battery-history-device" name="battery_device" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    <option value="">All devices</option>
                    @foreach($batteryHistoryDevices as $deviceOption)
                        <option value="{{ $deviceOption->id }}" @selected((string) $batteryHistoryDevice === (string) $deviceOption->id)>{{ $deviceOption->device_code }}</option>
                    @endforeach
                </select>
                <label for="battery-history-sort" class="visually-hidden">Sort battery readings</label>
                <select id="battery-history-sort" name="battery_sort" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    <option value="recorded" @selected($batteryHistorySort === 'recorded')>Newest by device time</option>
                    <option value="received" @selected($batteryHistorySort === 'received')>Newest by received time</option>
                </select>
                <label for="battery-history-per-page" class="visually-hidden">Rows per page</label>
                <select id="battery-history-per-page" name="battery_per_page" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    @foreach([25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($batteryHistoryPerPage === $option)>{{ $option }} rows</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-sm btn-outline-forest">Apply</button></noscript>
            </form>
        </div>
    </div>

    @if($batteryHistory->isEmpty())
        <div class="text-center text-muted py-5 px-3" role="status">
            <i class="bi bi-inbox d-block mb-2" aria-hidden="true"></i>
            <p class="fw-medium text-body mb-1">No battery readings to list</p>
            <p class="mb-0 device-monitoring-hint">Battery, voltage, and current reports appear here when devices send them.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 device-monitoring-table">
                <caption class="visually-hidden">Battery reports from devices, newest {{ $batteryHistorySort === 'received' ? 'received' : 'recorded' }} first</caption>
                <thead>
                    <tr>
                        <th scope="col">Sent by device</th>
                        <th scope="col">Received by server</th>
                        <th scope="col">Device</th>
                        <th scope="col">Hive</th>
                        <th scope="col" class="text-end">Battery level</th>
                        <th scope="col" class="text-end">Voltage</th>
                        <th scope="col" class="text-end">Current</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batteryHistory as $reading)
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
                            <td class="text-end">{{ $reading->battery_level !== null ? number_format($reading->battery_level, 1).'%' : '—' }}</td>
                            <td class="text-end">{{ $reading->battery_voltage !== null ? number_format($reading->battery_voltage, 2).' V' : '—' }}</td>
                            <td class="text-end">{{ $reading->battery_current !== null ? number_format($reading->battery_current, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($batteryHistory->hasPages())
            <div class="card-footer">{{ $batteryHistory->fragment('battery-readings')->links() }}</div>
        @endif
    @endif
</div>
