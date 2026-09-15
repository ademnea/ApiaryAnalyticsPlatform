@extends('layouts.app')

@section('title', 'All Anomalies')
@section('page-title', 'All Anomalies')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.anomaly.dashboard') }}">Anomaly Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">All Anomalies</li>
@endsection

@section('content')

    @include('admin.anomaly._subnav')

    {{-- ---- Category tabs ---- --}}
    <ul class="nav nav-tabs mb-3">
        @foreach($categories as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $filters['category'] === $key ? 'active' : '' }}"
                   href="{{ route('admin.anomaly.anomalies.index', array_filter(['category' => $key, 'status' => $filters['status']])) }}">
                    @if($key === 'hive')<i class="bi bi-hexagon me-1"></i>@elseif($key === 'device')<i class="bi bi-cpu me-1"></i>@endif
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- ---- Filters ---- --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="category" value="{{ $filters['category'] }}">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Severity</label>
                    <select name="severity" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($severities as $value => $label)
                            <option value="{{ $value }}" @selected($filters['severity'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Type</label>
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Hive</label>
                    <select name="hive_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($hives as $hive)
                            <option value="{{ $hive->id }}" @selected($filters['hive_id'] === $hive->id)>{{ $hive->display_name ?? $hive->hive_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Device</label>
                    <select name="device_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" @selected($filters['device_id'] === $device->id)>{{ $device->device_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 text-end">
                    <a href="{{ route('admin.anomaly.anomalies.index', ['category' => $filters['category']]) }}" class="btn btn-sm btn-outline-forest">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Detected from</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $filters['from']?->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:0.75rem;">Detected to</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $filters['to']?->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    {{-- ---- Results ---- --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-1"></i>{{ $categories[$filters['category']] }}</span>
            <span class="text-muted" style="font-size:0.72rem;font-weight:400;">{{ $anomalies->total() }} incident(s)</span>
        </div>

        @if($anomalies->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-shield-check d-block mb-2" style="font-size:1.8rem;color:var(--clr-forest-light);"></i>
                No anomalies match these filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Anomaly</th>
                            <th>Hive</th>
                            <th>Device</th>
                            <th>First Detected</th>
                            <th>Last Seen</th>
                            <th class="text-center">Count</th>
                            <th>Status</th>
                            <th class="text-end pe-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($anomalies as $anomaly)
                            <tr>
                                <td>
                                    <span class="badge {{ $anomaly->badgeClass() }}">
                                        <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
                                    </span>
                                    @unless($anomaly->isDeviceIssue())
                                        <div class="text-muted text-capitalize" style="font-size:0.7rem;">{{ $anomaly->sensor_type }}</div>
                                    @endunless
                                </td>
                                <td>
                                    @if($anomaly->hive)
                                        <a href="{{ route('admin.hives.show', $anomaly->hive) }}" class="text-decoration-none">{{ $anomaly->hive->display_name ?? $anomaly->hive->hive_code }}</a>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if($anomaly->device)
                                        <a href="{{ route('admin.iot-devices.show', $anomaly->device) }}" class="text-decoration-none">{{ $anomaly->device->device_code }}</a>
                                    @else
                                        <span class="text-muted">#{{ $anomaly->device_id }}</span>
                                    @endif
                                </td>
                                <td class="text-muted" title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">{{ $anomaly->detected_at->diffForHumans() }}</td>
                                <td class="text-muted" title="{{ $anomaly->last_seen_at?->format('Y-m-d H:i:s') }}">{{ ($anomaly->last_seen_at ?? $anomaly->detected_at)->diffForHumans() }}</td>
                                <td class="text-center">{{ $anomaly->occurrences }}</td>
                                <td>
                                    <span class="badge {{ $anomaly->statusBadgeClass() }} text-capitalize">{{ $anomaly->status() }}</span>
                                    @if($anomaly->auto_resolved)
                                        <div class="text-muted" style="font-size:0.68rem;">auto</div>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.anomaly.anomalies.show', $anomaly) }}" class="btn btn-sm btn-outline-forest" title="View anomaly"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($anomalies->hasPages())
            <div class="card-footer bg-white">{{ $anomalies->links() }}</div>
        @endif
    </div>

@endsection
