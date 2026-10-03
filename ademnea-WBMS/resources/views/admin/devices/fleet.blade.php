@extends('layouts.app')

@section('title', 'Device Fleet')
@section('page-title', 'Device Fleet')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Device Fleet</li>
@endsection

@push('styles')
    <style>
        .section-heading {
            display: flex; justify-content: space-between; align-items: flex-end;
            flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.85rem;
        }
        .section-heading h6 {
            margin: 0; font-size: 0.82rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.04em; color: var(--clr-forest);
        }
        .section-heading p { margin: 0.15rem 0 0; font-size: 0.76rem; color: var(--clr-muted); }
        .section-heading .meta-link { font-size: 0.76rem; color: var(--clr-forest-mid); text-decoration: none; white-space: nowrap; }
        .section-heading .meta-link:hover { text-decoration: underline; }

        .live-pill {
            display: inline-flex; align-items: center; gap: 0.3rem;
            font-size: 0.66rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase;
            color: var(--clr-forest); background: var(--clr-forest-pale);
            border-radius: 999px; padding: 0.15rem 0.55rem; vertical-align: middle; margin-left: 0.4rem;
        }
        .live-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--clr-forest-light); animation: livePulse 2s ease-in-out infinite; }
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }

        .device-row {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.6rem 0.9rem; border-bottom: 1px solid var(--clr-border);
            text-decoration: none; color: inherit; transition: background-color 0.12s ease;
        }
        .device-row:last-child { border-bottom: none; }
        .device-row:hover { background: var(--clr-canvas); }
        .device-row .severity-dot { flex: 0 0 auto; width: 8px; height: 8px; border-radius: 50%; }
        .severity-dot.is-critical { background: #b30000; }
        .severity-dot.is-warning  { background: #856404; }
        .device-row .device-code { font-weight: 600; font-size: 0.84rem; color: var(--clr-forest); }
        .device-row .device-meta { font-size: 0.72rem; color: var(--clr-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .device-row .device-value { margin-left: auto; flex: 0 0 auto; }
        .attention-list { max-height: 300px; overflow-y: auto; }

        .panel-empty { text-align: center; color: var(--clr-muted); font-size: 0.8rem; padding: 1.85rem 1rem; }
        .panel-empty i { font-size: 1.5rem; color: var(--clr-forest-light); display: block; margin-bottom: 0.4rem; }

        .stat-card.is-tight .stat-value { font-size: 1.3rem; }

        .health-pill { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 600; }
        .health-pill .dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto; }
        .health-pill.is-online .dot  { background: #2D6A4F; }
        .health-pill.is-warning .dot { background: #D4A017; }
        .health-pill.is-offline .dot { background: #b30000; }
        .health-pill.is-online  { color: #2D6A4F; }
        .health-pill.is-warning { color: #856404; }
        .health-pill.is-offline { color: #7F1D1D; }
    </style>
@endpush

@section('content')

    @php
        $hiveLabel = fn ($device) => $device->hive
            ? ($device->hive->display_name ?? $device->hive->hive_code) . ($device->hive->apiary ? ' · ' . $device->hive->apiary->name : '')
            : 'Unassigned';
    @endphp

    <div class="section-heading">
        <div>
            <h6>Fleet Status <span class="live-pill"><span class="dot"></span>Live</span></h6>
            <p>Current battery, signal, and connectivity for every active device — as of {{ $fleetGeneratedAt->format('H:i') }} today.</p>
        </div>
        <a href="{{ route('admin.iot-devices.index') }}" class="meta-link">
            View device registry <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    {{-- ---- KPI row ---- --}}
    <div class="row g-3 mb-3">
        @foreach([
            ['Active Devices', $kpis['total'], 'bi-cpu', 'blue', []],
            ['Online', $kpis['online'], 'bi-check-circle', 'green', ['health' => 'online']],
            ['Needs Attention', $kpis['warning'], 'bi-exclamation-triangle', $kpis['warning'] > 0 ? 'honey' : 'green', ['health' => 'warning']],
            ['Offline', $kpis['offline'], 'bi-wifi-off', $kpis['offline'] > 0 ? 'red' : 'green', ['health' => 'offline']],
            ['Open Device Issues', $kpis['open_issues'], 'bi-shield-exclamation', $kpis['open_issues'] > 0 ? 'red' : 'green', ['has_issues' => 1]],
        ] as [$label, $value, $icon, $tone, $query])
            <div class="col-6 col-md-4 col-xl">
                <a href="{{ route('admin.devices.fleet', $query) }}#devices" class="text-decoration-none text-reset">
                    <div class="stat-card is-tight h-100">
                        <div class="stat-icon {{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                        <div>
                            <div class="stat-value">{{ $value }}</div>
                            <div class="stat-label">{{ $label }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- ---- Attention panels ---- --}}
    <div class="row g-3 mb-4">
        {{-- Low battery --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-battery-half me-1"></i>Low Battery</span>
                    @if($lowBatteryDevices->isNotEmpty())
                        <span class="text-muted" style="font-size:0.72rem;font-weight:400;">{{ $lowBatteryDevices->count() }}</span>
                    @endif
                </div>
                @if($lowBatteryDevices->isEmpty())
                    <div class="panel-empty"><i class="bi bi-battery-full"></i>Every device is above the battery threshold.</div>
                @else
                    <div class="attention-list">
                        @foreach($lowBatteryDevices as $device)
                            @php
                                $critical = $health[$device->id]['is_critical_battery'];
                            @endphp
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="device-row">
                                <span class="severity-dot {{ $critical ? 'is-critical' : 'is-warning' }}"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="device-code">{{ $device->device_code }}</div>
                                    <div class="device-meta">{{ $hiveLabel($device) }}</div>
                                </div>
                                <span class="device-value badge {{ $critical ? 'badge-offline' : 'badge-warning' }}">{{ (int) $device->telemetry->battery_level }}%</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Weak signal --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-reception-1 me-1"></i>Weak Signal</span>
                    @if($weakSignalDevices->isNotEmpty())
                        <span class="text-muted" style="font-size:0.72rem;font-weight:400;">{{ $weakSignalDevices->count() }}</span>
                    @endif
                </div>
                @if($weakSignalDevices->isEmpty())
                    <div class="panel-empty"><i class="bi bi-reception-4"></i>Every device has a healthy signal.</div>
                @else
                    <div class="attention-list">
                        @foreach($weakSignalDevices as $device)
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="device-row">
                                <span class="severity-dot is-warning"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="device-code">{{ $device->device_code }}</div>
                                    <div class="device-meta">{{ $hiveLabel($device) }}</div>
                                </div>
                                <span class="device-value badge badge-warning">{{ (int) $device->telemetry->signal_strength }} dBm</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Not heard from --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-1"></i>Not Heard From</span>
                    @if($notHeardFromDevices->isNotEmpty())
                        <span class="text-muted" style="font-size:0.72rem;font-weight:400;">{{ $notHeardFromDevices->count() }}</span>
                    @endif
                </div>
                @if($notHeardFromDevices->isEmpty())
                    <div class="panel-empty"><i class="bi bi-broadcast"></i>Every device has checked in recently.</div>
                @else
                    <div class="attention-list">
                        @foreach($notHeardFromDevices as $device)
                            @php
                                $lastContact = $health[$device->id]['last_contact'];
                            @endphp
                            <a href="{{ route('admin.iot-devices.show', $device) }}" class="device-row">
                                <span class="severity-dot is-critical"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="device-code">{{ $device->device_code }}</div>
                                    <div class="device-meta">{{ $hiveLabel($device) }}</div>
                                </div>
                                <span class="device-value badge badge-offline" title="{{ $lastContact?->format('Y-m-d H:i:s') ?? 'No contact recorded' }}">
                                    {{ $lastContact ? $lastContact->diffForHumans(null, true) : 'Never' }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Open device issues (incidents) --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-exclamation me-1"></i>Open Issues</span>
                    <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'device', 'status' => 'unresolved']) }}" style="font-size:0.72rem;font-weight:400;">View all</a>
                </div>
                @if($latestIssues->isEmpty())
                    <div class="panel-empty"><i class="bi bi-shield-check"></i>No open device issues.</div>
                @else
                    <div class="attention-list">
                        @foreach($latestIssues as $issue)
                            <a href="{{ route('admin.anomaly.anomalies.show', $issue) }}" class="device-row">
                                <span class="severity-dot {{ $issue->severity() === 'critical' ? 'is-critical' : 'is-warning' }}"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="device-code">{{ $issue->device->device_code ?? '#' . $issue->device_id }}</div>
                                    <div class="device-meta">
                                        {{ $issue->label() }} · since {{ $issue->detected_at->diffForHumans(null, true) }}
                                    </div>
                                </div>
                                <span class="device-value badge {{ $issue->statusBadgeClass() }} text-capitalize">{{ $issue->status() }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ---- All devices ---- --}}
    <div class="section-heading" id="devices">
        <div>
            <h6>All Devices</h6>
            <p>{{ $fleet->total() }} of {{ $kpis['total'] }} active device(s) shown.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Health</label>
                    <select name="health" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['online' => 'Online', 'warning' => 'Needs attention', 'offline' => 'Offline'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('health') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Hardware Team</label>
                    <select name="hardware_team_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All teams</option>
                        @foreach($hardwareTeams as $team)
                            <option value="{{ $team->id }}" @selected(request('hardware_team_id') == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Assignment</label>
                    <select name="assignment" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="assigned" @selected(request('assignment') === 'assigned')>Assigned to a hive</option>
                        <option value="unassigned" @selected(request('assignment') === 'unassigned')>Unassigned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="has_issues" value="1" id="has_issues"
                               @checked(request()->boolean('has_issues')) onchange="this.form.submit()">
                        <label class="form-check-label" for="has_issues" style="font-size:0.8rem;">With open issues</label>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <a href="{{ route('admin.devices.fleet') }}" class="btn btn-sm btn-outline-forest"><i class="bi bi-x-circle me-1"></i>Clear filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Hive</th>
                        <th>Health</th>
                        <th>Battery</th>
                        <th>Signal</th>
                        <th>Last Contact</th>
                        <th class="text-end pe-3">Open Issues</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fleet as $device)
                        @php
                            $deviceHealth = $health[$device->id];
                            $healthClass = match ($deviceHealth['status']) { 'offline' => 'is-offline', 'warning' => 'is-warning', default => 'is-online' };
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.iot-devices.show', $device) }}" class="fw-medium text-decoration-none">{{ $device->device_code }}</a>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $device->hardwareTeam?->name }}</div>
                            </td>
                            <td>
                                @if($device->hive)
                                    <a href="{{ route('admin.hives.show', $device->hive) }}" class="text-decoration-none">{{ $device->hive->display_name ?? $device->hive->hive_code }}</a>
                                @else
                                    <span class="badge badge-pending">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <span class="health-pill {{ $healthClass }}">
                                    <span class="dot"></span>{{ $deviceHealth['never_reported'] ? 'Never reported' : ucfirst($deviceHealth['status']) }}
                                </span>
                            </td>
                            <td class="{{ $deviceHealth['is_low_battery'] ? ($deviceHealth['is_critical_battery'] ? 'text-danger fw-semibold' : 'text-warning fw-semibold') : '' }}">
                                {{ $deviceHealth['battery_level'] !== null ? (int) $deviceHealth['battery_level'] . '%' : '—' }}
                            </td>
                            <td class="{{ $deviceHealth['is_weak_signal'] ? 'text-warning fw-semibold' : '' }}">
                                {{ $deviceHealth['signal_strength'] !== null ? (int) $deviceHealth['signal_strength'] . ' dBm' : '—' }}
                            </td>
                            <td class="text-muted" @if($deviceHealth['last_contact']) title="{{ $deviceHealth['last_contact']->format('Y-m-d H:i:s') }}" @endif>
                                {{ $deviceHealth['last_contact']?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="text-end pe-3">
                                @if($device->open_issues_count > 0)
                                    <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'device', 'device_id' => $device->id, 'status' => 'unresolved']) }}"
                                       class="badge badge-offline text-decoration-none">{{ $device->open_issues_count }} open</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No active devices match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($fleet->hasPages())
            <div class="card-footer bg-white">{{ $fleet->links() }}</div>
        @endif
    </div>

@endsection
