@extends('layouts.app')

@section('title', 'System Alerts')
@section('page-title', 'System Alerts')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">System Alerts</li>
@endsection

@section('content')

    @php
        $typeBadge = fn (string $type) => match ($type) {
            'critical_event', 'malfunction' => 'badge-offline',
            'low_battery', 'weak_signal', 'feed_required' => 'badge-warning',
            default => 'badge-info',
        };
    @endphp

    <p class="text-muted mb-3" style="font-size:0.82rem;">
        Every alert sent to a farmer — from the anomaly engine and scheduled threshold checks.
        Repeats of an ongoing anomaly don't create new alerts; follow the incident link for the full history.
    </p>

    {{-- ---- KPI row ---- --}}
    <div class="row g-3 mb-3">
        @foreach([
            ['Last 24 Hours', $kpis['last_24h'], 'bi-bell', 'honey', []],
            ['Unread by Farmers', $kpis['unread'], 'bi-envelope', $kpis['unread'] > 0 ? 'red' : 'green', ['read' => 'unread']],
            ['From Anomalies (7 Days)', $kpis['from_anomalies'], 'bi-shield-exclamation', 'blue', ['source' => 'anomaly']],
            ['Total Sent', $kpis['total'], 'bi-archive', 'green', []],
        ] as [$label, $value, $icon, $tone, $query])
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.alerts.index', $query) }}" class="text-decoration-none text-reset">
                    <div class="stat-card h-100">
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

    {{-- ---- Filters ---- --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Type</label>
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Read</label>
                    <select name="read" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        <option value="unread" @selected($filters['read'] === 'unread')>Unread</option>
                        <option value="read" @selected($filters['read'] === 'read')>Read</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Source</label>
                    <select name="source" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        <option value="anomaly" @selected($filters['source'] === 'anomaly')>Anomaly engine</option>
                        <option value="other" @selected($filters['source'] === 'other')>Other checks</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Hive</label>
                    <select name="hive_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All hives</option>
                        @foreach($hives as $hive)
                            <option value="{{ $hive->id }}" @selected($filters['hive_id'] === $hive->id)>{{ $hive->display_name ?? $hive->hive_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 text-end">
                    <a href="{{ route('admin.alerts.index') }}" class="btn btn-sm btn-outline-forest"><i class="bi bi-x-circle me-1"></i>Clear filters</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ---- Alerts ---- --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-bell me-1"></i>Alerts</span>
            <span class="text-muted" style="font-size:0.72rem;font-weight:400;">{{ $alerts->total() }} alert(s)</span>
        </div>

        @if($alerts->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-bell-slash d-block mb-2" style="font-size:1.8rem;color:var(--clr-forest-light);"></i>
                No alerts match these filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Sent</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Farmer</th>
                            <th>Hive</th>
                            <th>Read</th>
                            <th>Incident</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr>
                                <td class="text-muted text-nowrap" title="{{ $alert->created_at?->format('Y-m-d H:i:s') }}">{{ $alert->created_at?->diffForHumans() ?? '—' }}</td>
                                <td><span class="badge {{ $typeBadge($alert->type) }}">{{ ucwords(str_replace('_', ' ', $alert->type)) }}</span></td>
                                <td style="font-size:0.8rem;max-width:340px;">{{ $alert->message }}</td>
                                <td>
                                    @if($alert->farmer)
                                        @can('manage-farmers')
                                            <a href="{{ route('admin.farmers.show', $alert->farmer) }}" class="text-decoration-none">{{ $alert->farmer->full_name }}</a>
                                        @else
                                            {{ $alert->farmer->full_name }}
                                        @endcan
                                    @else
                                        <span class="text-muted">#{{ $alert->farmer_id }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($alert->hive)
                                        <a href="{{ route('admin.hives.show', $alert->hive) }}" class="text-decoration-none">{{ $alert->hive->display_name ?? $alert->hive->hive_code }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($alert->is_read)
                                        <span class="badge badge-active" title="{{ $alert->read_at?->format('Y-m-d H:i') }}">Read</span>
                                    @else
                                        <span class="badge badge-pending">Unread</span>
                                    @endif
                                </td>
                                <td>
                                    @if($alert->sourceAnomaly)
                                        @canany(['view-anomaly-analytics', 'view-device-fleet'])
                                            <a href="{{ route('admin.anomaly.anomalies.show', $alert->sourceAnomaly) }}"
                                               class="badge {{ $alert->sourceAnomaly->statusBadgeClass() }} text-decoration-none text-capitalize">
                                                #{{ $alert->sourceAnomaly->id }} · {{ $alert->sourceAnomaly->status() }}
                                            </a>
                                        @else
                                            <span class="badge {{ $alert->sourceAnomaly->statusBadgeClass() }} text-capitalize">{{ $alert->sourceAnomaly->status() }}</span>
                                        @endcanany
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($alerts->hasPages())
            <div class="card-footer bg-white">{{ $alerts->links() }}</div>
        @endif
    </div>

@endsection
