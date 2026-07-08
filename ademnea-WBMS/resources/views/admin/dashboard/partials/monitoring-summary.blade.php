{{--
    Partial: dashboard/partials/monitoring-summary.blade.php
    Receives:
      $monitoring (array from DashboardService::getHiveMonitoringSummary())
    SRS: REQ-DASH-02 – Real-time hive monitoring summary:
         avg temp, humidity, CO₂, weight, battery/device status.
--}}

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-activity me-2 text-success"></i>Hive Monitoring Summary</span>
        <a href="{{ route('admin.monitoring.temperature') }}"
           class="btn btn-sm btn-outline-forest">
            View Sensors <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body">

        {{-- Sensor averages row --}}
        <div class="row g-3 mb-3">

            {{-- Average Temperature --}}
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-3 p-3 rounded"
                     style="background:var(--clr-canvas);border:1px solid var(--clr-border);">
                    <div class="stat-icon red flex-shrink-0">
                        <i class="bi bi-thermometer-half"></i>
                    </div>
                    <div>
                        @if($monitoring['avg_temperature'] !== null)
                            <div class="stat-value" style="font-size:1.25rem;">
                                {{ number_format($monitoring['avg_temperature'], 1) }}&deg;C
                            </div>
                        @else
                            <div class="stat-value text-muted" style="font-size:1rem;">—</div>
                            {{-- TODO: populate from HiveTemperature model --}}
                        @endif
                        <div class="stat-label">Avg Temp</div>
                    </div>
                </div>
            </div>

            {{-- Average Humidity --}}
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-3 p-3 rounded"
                     style="background:var(--clr-canvas);border:1px solid var(--clr-border);">
                    <div class="stat-icon blue flex-shrink-0">
                        <i class="bi bi-droplet-half"></i>
                    </div>
                    <div>
                        @if($monitoring['avg_humidity'] !== null)
                            <div class="stat-value" style="font-size:1.25rem;">
                                {{ number_format($monitoring['avg_humidity'], 1) }}%
                            </div>
                        @else
                            <div class="stat-value text-muted" style="font-size:1rem;">—</div>
                            {{-- TODO: populate from HiveHumidity model --}}
                        @endif
                        <div class="stat-label">Avg Humidity</div>
                    </div>
                </div>
            </div>

            {{-- Average CO₂ --}}
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-3 p-3 rounded"
                     style="background:var(--clr-canvas);border:1px solid var(--clr-border);">
                    <div class="stat-icon honey flex-shrink-0">
                        <i class="bi bi-wind"></i>
                    </div>
                    <div>
                        @if($monitoring['avg_co2'] !== null)
                            <div class="stat-value" style="font-size:1.25rem;">
                                {{ number_format($monitoring['avg_co2'], 0) }} ppm
                            </div>
                        @else
                            <div class="stat-value text-muted" style="font-size:1rem;">—</div>
                            {{-- TODO: populate from HiveCo2 model --}}
                        @endif
                        <div class="stat-label">Avg CO₂</div>
                    </div>
                </div>
            </div>

            {{-- Average Hive Weight --}}
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-3 p-3 rounded"
                     style="background:var(--clr-canvas);border:1px solid var(--clr-border);">
                    <div class="stat-icon green flex-shrink-0">
                        <i class="bi bi-speedometer"></i>
                    </div>
                    <div>
                        @if($monitoring['avg_weight'] !== null)
                            <div class="stat-value" style="font-size:1.25rem;">
                                {{ number_format($monitoring['avg_weight'], 1) }} kg
                            </div>
                        @else
                            <div class="stat-value text-muted" style="font-size:1rem;">—</div>
                            {{-- TODO: populate from HiveWeight model --}}
                        @endif
                        <div class="stat-label">Avg Weight</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Device status row --}}
        <div class="row g-3">

            {{-- Active vs Offline Devices --}}
            <div class="col-md-4">
                <div class="p-3 rounded" style="background:var(--clr-canvas);border:1px solid var(--clr-border);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:0.78rem;font-weight:600;color:var(--clr-muted);">
                            <i class="bi bi-cpu me-1"></i>Device Status
                        </span>
                        <a href="{{ route('admin.devices.fleet') }}"
                           style="font-size:0.72rem;color:var(--clr-forest-mid);">Fleet Health →</a>
                    </div>
                    {{-- TODO: replace zeroes with IotDevice counts once model exists --}}
                    @php
                        $active  = $monitoring['active_devices']  ?? 0;
                        $offline = $monitoring['offline_devices'] ?? 0;
                        $total   = $active + $offline;
                        $pct     = $total > 0 ? round(($active / $total) * 100) : 0;
                    @endphp
                    @if($monitoring['active_devices'] !== null)
                        <div class="d-flex gap-3 mb-2">
                            <span class="badge badge-active py-1 px-2">
                                <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>
                                {{ $active }} Online
                            </span>
                            <span class="badge badge-offline py-1 px-2">
                                <i class="bi bi-circle me-1" style="font-size:0.5rem;"></i>
                                {{ $offline }} Offline
                            </span>
                        </div>
                        <div class="progress" style="height:6px;border-radius:3px;">
                            <div class="progress-bar bg-success" style="width:{{ $pct }}%;"></div>
                        </div>
                        <div style="font-size:0.7rem;color:var(--clr-muted);margin-top:4px;">
                            {{ $pct }}% online
                        </div>
                    @else
                        <div style="font-size:0.8rem;color:var(--clr-muted);">
                            <i class="bi bi-info-circle me-1"></i>
                            IoT module not yet integrated
                        </div>
                    @endif
                </div>
            </div>

            {{-- Battery Alerts --}}
            <div class="col-md-4">
                <div class="p-3 rounded" style="background:var(--clr-canvas);border:1px solid var(--clr-border);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:0.78rem;font-weight:600;color:var(--clr-muted);">
                            <i class="bi bi-battery-half me-1"></i>Battery Status
                        </span>
                    </div>
                    @if($monitoring['low_battery_count'] !== null)
                        @if($monitoring['low_battery_count'] > 0)
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-warning py-1 px-2">
                                    {{ $monitoring['low_battery_count'] }} Low Battery
                                </span>
                            </div>
                            <div style="font-size:0.72rem;color:#856404;margin-top:6px;">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Devices need attention
                            </div>
                        @else
                            <span class="badge badge-active py-1 px-2">All Good</span>
                            <div style="font-size:0.72rem;color:var(--clr-muted);margin-top:6px;">
                                No low-battery alerts
                            </div>
                        @endif
                    @else
                        <div style="font-size:0.8rem;color:var(--clr-muted);">
                            <i class="bi bi-info-circle me-1"></i>
                            IoT module not yet integrated
                        </div>
                    @endif
                </div>
            </div>

            {{-- Hive Status Breakdown --}}
            <div class="col-md-4">
                <div class="p-3 rounded" style="background:var(--clr-canvas);border:1px solid var(--clr-border);">
                    <div style="font-size:0.78rem;font-weight:600;color:var(--clr-muted);margin-bottom:8px;">
                        <i class="bi bi-hexagon me-1"></i>Hive Status Breakdown
                    </div>
                    @forelse($monitoring['hive_status_breakdown'] as $row)
                        @php
                            $statusColor = match($row->status) {
                                'active'           => 'badge-active',
                                'queenless'        => 'badge-offline',
                                'absconded'        => 'badge-offline',
                                'under_inspection' => 'badge-warning',
                                default            => 'badge-pending',
                            };
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge {{ $statusColor }}" style="text-transform:capitalize;font-size:0.7rem;">
                                {{ str_replace('_', ' ', $row->status) }}
                            </span>
                            <span style="font-size:0.78rem;font-weight:600;">{{ $row->total }}</span>
                        </div>
                    @empty
                        <div style="font-size:0.8rem;color:var(--clr-muted);">No hives registered yet.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
