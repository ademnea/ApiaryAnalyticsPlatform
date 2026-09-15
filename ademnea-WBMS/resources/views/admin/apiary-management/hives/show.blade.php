@extends('layouts.app')

@section('title', $hive->display_name)
@section('page-title', $hive->display_name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hives.index') }}">Hives</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $hive->hybrid_identifier }}</li>
@endsection

@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>Hive Info</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Code</dt>
                    <dd class="col-sm-8"><code>{{ $hive->hybrid_identifier }}</code></dd>

                    <dt class="col-sm-4 text-muted">Apiary</dt>
                    <dd class="col-sm-8"><a href="{{ route('admin.apiaries.show', $hive->apiary) }}">{{ $hive->apiary->name }}</a></dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $hive->current_status === 'Active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($hive->current_status) }}
                        </span>
                    </dd>

                    <dt class="col-sm-4 text-muted">Type</dt>
                    <dd class="col-sm-8">{{ $hive->hive_type }}</dd>

                    <dt class="col-sm-4 text-muted">Queen</dt>
                    <dd class="col-sm-8">{{ ucfirst($hive->queen_status) }}</dd>

                    <dt class="col-sm-4 text-muted">Origin</dt>
                    <dd class="col-sm-8">{{ ucfirst($hive->colony_origin) ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Installed</dt>
                    <dd class="col-sm-8">{{ $hive->installation_date?->format('d M Y') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Coordinates</dt>
                    <dd class="col-sm-8">{{ $hive->latitude }}, {{ $hive->longitude }}</dd>

                    <dt class="col-sm-4 text-muted">Notes</dt>
                    <dd class="col-sm-8">{{ $hive->notes ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-arrow-repeat me-1"></i>Change Status</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.hives.updateStatus', $hive) }}">
                    @csrf
                    @method('PATCH')

                    <div class="row g-2">
                        <div class="col-md-5">
                            <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror" required>
                                <option value="">— Select new status —</option>
                                @foreach (['Active', 'Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned'] as $option)
                                    @continue($option === $hive->current_status)
                                    <option value="{{ $option }}" @selected(old('status') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <textarea name="change_notes" rows="1" class="form-control form-control-sm @error('change_notes') is-invalid @enderror"
                                      placeholder="Reason (optional)">{{ old('change_notes') }}</textarea>
                            @error('change_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-shield-exclamation me-1"></i>Anomalies</span>
                <span style="font-size:0.72rem;font-weight:400;">
                    @if($hasUnresolvedAnomalies)
                        <span class="badge badge-offline me-2">Unresolved</span>
                    @else
                        <span class="badge badge-active me-2">All clear</span>
                    @endif
                    @can('view-anomaly-analytics')
                        <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'all', 'hive_id' => $hive->id]) }}">View all</a>
                    @endcan
                </span>
            </div>
            @if($latestAnomalies->isEmpty())
                <div class="card-body text-center text-muted py-3" style="font-size:0.82rem;">
                    No anomalies recorded for this hive.
                </div>
            @else
                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size:0.82rem;">
                        <thead>
                            <tr><th>Anomaly</th><th>Since</th><th class="text-center">Count</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($latestAnomalies as $anomaly)
                                <tr>
                                    <td>
                                        @can('view-anomaly-analytics')
                                            <a href="{{ route('admin.anomaly.anomalies.show', $anomaly) }}" class="badge {{ $anomaly->badgeClass() }} text-decoration-none">
                                                <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
                                            </a>
                                        @else
                                            <span class="badge {{ $anomaly->badgeClass() }}"><i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}</span>
                                        @endcan
                                        <div class="text-muted text-capitalize" style="font-size:0.7rem;">{{ $anomaly->isDeviceIssue() ? 'Device issue' : $anomaly->sensor_type }}</div>
                                    </td>
                                    <td class="text-muted" title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">{{ $anomaly->detected_at->diffForHumans() }}</td>
                                    <td class="text-center">{{ $anomaly->occurrences }}</td>
                                    <td><span class="badge {{ $anomaly->statusBadgeClass() }} text-capitalize">{{ $anomaly->status() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-1"></i>Status History</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr><th>When</th><th>From</th><th>To</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($hive->statusHistory as $entry)
                            <tr>
                                <td>{{ $entry->transitioned_at->format('d M Y H:i') }}</td>
                                <td>{{ $entry->previous_status ?? '—' }}</td>
                                <td>{{ $entry->new_status }}</td>
                                <td>{{ $entry->reason_note ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No status changes recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
