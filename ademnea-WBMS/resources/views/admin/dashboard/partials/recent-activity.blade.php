{{--
    Partial: dashboard/partials/recent-activity.blade.php
    Receives: $activity (array from DashboardService::getRecentActivity())
    SRS: REQ-DASH-05 – Recent farmers, farms, hives, sensor readings, inspections.
--}}

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-clock-history me-2 text-success"></i>Recent Activity
    </div>
    <div class="card-body p-0">

        <ul class="nav nav-tabs px-3 pt-2 border-0" id="activityTabs" role="tablist"
            style="gap:0.25rem;">
            @if($isAdmin)
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-farmers" data-bs-toggle="tab"
                        data-bs-target="#pane-farmers" type="button" role="tab"
                        style="font-size:0.78rem;">
                    <i class="bi bi-people me-1"></i>Farmers
                </button>
            </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $isAdmin ? '' : 'active' }}" id="tab-apiaries" data-bs-toggle="tab"
                        data-bs-target="#pane-apiaries" type="button" role="tab"
                        style="font-size:0.78rem;">
                    <i class="bi bi-building me-1"></i>Farms
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-hives" data-bs-toggle="tab"
                        data-bs-target="#pane-hives" type="button" role="tab"
                        style="font-size:0.78rem;">
                    <i class="bi bi-hexagon me-1"></i>Hives
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-changes" data-bs-toggle="tab"
                        data-bs-target="#pane-changes" type="button" role="tab"
                        style="font-size:0.78rem;">
                    <i class="bi bi-arrow-left-right me-1"></i>Status Changes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-sensors" data-bs-toggle="tab"
                        data-bs-target="#pane-sensors" type="button" role="tab"
                        style="font-size:0.78rem;">
                    <i class="bi bi-broadcast me-1"></i>Sensor Readings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-inspections" data-bs-toggle="tab"
                        data-bs-target="#pane-inspections" type="button" role="tab"
                        style="font-size:0.78rem;">
                    <i class="bi bi-clipboard-check me-1"></i>Inspections
                </button>
            </li>
        </ul>

        <div class="tab-content" id="activityTabContent">

            {{-- ── Recent Farmers — admin only ──────────────────────── --}}
            @if($isAdmin)
            <div class="tab-pane fade show active" id="pane-farmers" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Country</th>
                                <th>Status</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activity['recent_farmers'] as $farmer)
                                <tr>
                                    <td>{{ $farmer->full_name }}</td>
                                    <td>{{ $farmer->phone_number }}</td>
                                    <td>{{ $farmer->country }}</td>
                                    <td>
                                        <span class="badge {{ $farmer->profile_status === 'active' ? 'badge-active' : 'badge-pending' }}"
                                              style="text-transform:capitalize;">
                                            {{ $farmer->profile_status }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ $farmer->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-3">
                                        No farmers registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($activity['recent_farmers']->isNotEmpty())
                    <div class="px-3 py-2 border-top" style="background:#fafcfa;">
                        <a href="{{ route('admin.farmers.index') }}"
                           style="font-size:0.78rem;color:var(--clr-forest-mid);">
                            View all farmers →
                        </a>
                    </div>
                @endif
            </div>
            @endif {{-- end isAdmin farmers pane --}}

            {{-- ── Recent Farms (Apiaries) ───────────────────────────── --}}
            <div class="tab-pane fade {{ $isAdmin ? '' : 'show active' }}" id="pane-apiaries" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Farm Name</th>
                                <th>Country</th>
                                <th>Region</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activity['recent_apiaries'] as $apiary)
                                <tr>
                                    <td>{{ $apiary->name }}</td>
                                    <td>{{ $apiary->country }}</td>
                                    <td>{{ $apiary->region ?? '—' }}</td>
                                    <td class="text-muted">{{ $apiary->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-3">
                                        No farms registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($activity['recent_apiaries']->isNotEmpty())
                    <div class="px-3 py-2 border-top" style="background:#fafcfa;">
                        <a href="{{ route('admin.apiaries.index') }}"
                           style="font-size:0.78rem;color:var(--clr-forest-mid);">
                            View all farms →
                        </a>
                    </div>
                @endif
            </div>

            {{-- ── Recent Hives ──────────────────────────────────────── --}}
            <div class="tab-pane fade" id="pane-hives" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Hive Name</th>
                                <th>Farm</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activity['recent_hives'] as $hive)
                                <tr>
                                    <td>{{ $hive->display_name }}</td>
                                    <td>{{ $hive->apiary->name ?? '—' }}</td>
                                    <td>{{ $hive->hive_type }}</td>
                                    <td>
                                        <span class="badge {{ $hive->status === 'active' ? 'badge-active' : 'badge-pending' }}"
                                              style="text-transform:capitalize;">
                                            {{ str_replace('_', ' ', $hive->status) }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ $hive->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-3">
                                        No hives registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($activity['recent_hives']->isNotEmpty())
                    <div class="px-3 py-2 border-top" style="background:#fafcfa;">
                        <a href="{{ route('admin.hives.index') }}"
                           style="font-size:0.78rem;color:var(--clr-forest-mid);">
                            View all hives →
                        </a>
                    </div>
                @endif
            </div>

            {{-- ── Recent Hive Status Changes ─────────────────────────── --}}
            <div class="tab-pane fade" id="pane-changes" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Hive</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Changed By</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activity['recent_status_changes'] as $change)
                                <tr>
                                    <td>{{ $change->hive->display_name ?? 'Hive #'.$change->hive_id }}</td>
                                    <td>
                                        <span class="badge badge-pending" style="text-transform:capitalize;">
                                            {{ str_replace('_', ' ', $change->previous_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $change->new_status === 'active' ? 'badge-active' : 'badge-offline' }}"
                                              style="text-transform:capitalize;">
                                            {{ str_replace('_', ' ', $change->new_status) }}
                                        </span>
                                    </td>
                                    <td>{{ $change->changedBy->name ?? 'System' }}</td>
                                    <td class="text-muted">{{ $change->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-3">
                                        No status changes recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Recent Sensor Readings ───────────────────────────── --}}
            <div class="tab-pane fade" id="pane-sensors" role="tabpanel">
                @if($activity['recent_sensor_readings']->isEmpty())
                    <div class="p-4 text-center text-muted" style="font-size:0.85rem;">
                        <i class="bi bi-broadcast display-6 d-block mb-2 opacity-25"></i>
                        No sensor readings received yet.
                        <br>
                        <a href="{{ route('admin.anomaly.dashboard') }}" class="btn btn-sm btn-outline-forest mt-2">
                            View Monitoring
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Sensor</th>
                                    <th>Hive</th>
                                    <th>Value</th>
                                    <th>Recorded</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activity['recent_sensor_readings'] as $reading)
                                    <tr>
                                        <td><i class="bi {{ $reading['icon'] }} me-1 text-muted"></i>{{ $reading['sensor_type'] }}</td>
                                        <td>{{ $reading['hive']->display_name ?? $reading['hive']->hive_code ?? 'Unassigned' }}</td>
                                        <td>
                                            {{ $reading['value'] }}
                                            @if($reading['suspect'])
                                                <span class="badge badge-offline ms-1" style="font-size:0.65rem;" title="Flagged by the anomaly rules engine">
                                                    <i class="bi bi-flag-fill"></i> flagged
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $reading['recorded_at']->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 border-top" style="background:#fafcfa;">
                        <a href="{{ route('admin.anomaly.dashboard') }}"
                           style="font-size:0.78rem;color:var(--clr-forest-mid);">
                            View all monitoring →
                        </a>
                    </div>
                @endif
            </div>

            {{-- ── Recent Inspections ───────────────────────────────── --}}
            <div class="tab-pane fade" id="pane-inspections" role="tabpanel">
                @if($activity['recent_inspections']->isEmpty())
                    <div class="p-4 text-center text-muted" style="font-size:0.85rem;">
                        <i class="bi bi-clipboard-check display-6 d-block mb-2 opacity-25"></i>
                        No inspections logged yet.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Hive</th>
                                    <th>Strength</th>
                                    <th>Inspector</th>
                                    <th>Inspected</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activity['recent_inspections'] as $inspection)
                                    <tr>
                                        <td>{{ $inspection->hive->display_name ?? $inspection->hive->hive_code ?? 'Hive #'.$inspection->hive_id }}</td>
                                        <td>
                                            @if($inspection->strength_rating)
                                                <span class="badge badge-pending" style="text-transform:capitalize;">{{ $inspection->strength_rating }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $inspection->inspector->name ?? 'Unknown' }}</td>
                                        <td class="text-muted">{{ $inspection->inspected_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
