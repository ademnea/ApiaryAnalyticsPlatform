@extends('layouts.app')

@section('title', $anomaly->label())
@section('page-title', $anomaly->label())
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.anomaly.anomalies.index', ['category' => $anomaly->category()]) }}">All Anomalies</a></li>
    <li class="breadcrumb-item active" aria-current="page">#{{ $anomaly->id }}</li>
@endsection

@section('content')

    @include('admin.anomaly._subnav')

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="badge {{ $anomaly->badgeClass() }}" style="font-size:0.8rem;">
            <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
        </span>
        <span class="badge {{ $anomaly->statusBadgeClass() }} text-capitalize" style="font-size:0.8rem;">{{ $anomaly->status() }}</span>
        <span class="badge badge-pending text-capitalize" style="font-size:0.8rem;">{{ $anomaly->severity() }}</span>
        <span class="text-muted" style="font-size:0.8rem;">
            <i class="bi {{ $anomaly->isDeviceIssue() ? 'bi-cpu' : 'bi-hexagon' }} me-1"></i>{{ $anomaly->isDeviceIssue() ? 'Device issue' : 'Hive condition' }}
        </span>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- ---- Incident details ---- --}}
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle me-1"></i>Incident Details</div>
                <div class="card-body">
                    <dl class="row mb-0" style="font-size:0.85rem;">
                        <dt class="col-sm-4 text-muted">Hive</dt>
                        <dd class="col-sm-8">
                            @if($anomaly->hive)
                                <a href="{{ route('admin.hives.show', $anomaly->hive) }}">{{ $anomaly->hive->display_name ?? $anomaly->hive->hive_code }}</a>
                                @if($anomaly->hive->apiary)<span class="text-muted"> · {{ $anomaly->hive->apiary->name }}</span>@endif
                            @else
                                <span class="text-muted">Device was not assigned to a hive</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Device</dt>
                        <dd class="col-sm-8">
                            @if($anomaly->device && ! $anomaly->device->trashed())
                                <a href="{{ route('admin.iot-devices.show', $anomaly->device) }}">{{ $anomaly->device->device_code }}</a>
                            @else
                                {{ $anomaly->device->device_code ?? '#' . $anomaly->device_id }} <span class="text-muted">(removed)</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Sensor</dt>
                        <dd class="col-sm-8 text-capitalize">{{ $anomaly->sensor_type }}</dd>

                        <dt class="col-sm-4 text-muted">First Detected</dt>
                        <dd class="col-sm-8">{{ $anomaly->detected_at->format('d M Y, H:i:s') }} <span class="text-muted">({{ $anomaly->detected_at->diffForHumans() }})</span></dd>

                        <dt class="col-sm-4 text-muted">Last Seen</dt>
                        <dd class="col-sm-8">
                            @php($lastSeen = $anomaly->last_seen_at ?? $anomaly->detected_at)
                            {{ $lastSeen->format('d M Y, H:i:s') }} <span class="text-muted">({{ $lastSeen->diffForHumans() }})</span>
                        </dd>

                        <dt class="col-sm-4 text-muted">Occurrences</dt>
                        <dd class="col-sm-8">{{ $anomaly->occurrences }}</dd>

                        <dt class="col-sm-4 text-muted">First Flagged Value</dt>
                        <dd class="col-sm-8"><code>{{ \App\Models\SensorAnomaly::formatValues($anomaly->record_value) }}</code></dd>

                        <dt class="col-sm-4 text-muted">Latest Value</dt>
                        <dd class="col-sm-8"><code>{{ \App\Models\SensorAnomaly::formatValues($anomaly->last_record_value ?? $anomaly->record_value) }}</code></dd>

                        <dt class="col-sm-4 text-muted">Detection</dt>
                        <dd class="col-sm-8">
                            <span class="text-uppercase">{{ $anomaly->detection_layer }}</span>
                            @if($anomaly->anomaly_score !== null)<span class="text-muted"> · score {{ round($anomaly->anomaly_score, 3) }}</span>@endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- ---- Alerts sent ---- --}}
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-bell me-1"></i>Alerts Sent</div>
                @if($anomaly->alerts->isEmpty())
                    <div class="card-body text-muted text-center py-4" style="font-size:0.82rem;">
                        No farmer alert was sent for this incident
                        @if(! $anomaly->hive_id)
                            — the device isn't assigned to a hive, so there's no farmer to notify.
                        @else
                            — it may have been suppressed by the alert cooldown.
                        @endif
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($anomaly->alerts as $alert)
                            <div class="list-group-item" style="font-size:0.82rem;">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <i class="bi bi-person me-1 text-muted"></i>
                                        {{ $alert->farmer ? trim($alert->farmer->first_name . ' ' . $alert->farmer->last_name) : 'Farmer #' . $alert->farmer_id }}
                                    </span>
                                    <span class="text-muted" title="{{ $alert->created_at?->format('Y-m-d H:i:s') }}">{{ $alert->created_at?->diffForHumans() }}</span>
                                </div>
                                <div class="text-muted mt-1">{{ $alert->message }}</div>
                                <span class="badge {{ $alert->is_read ? 'badge-active' : 'badge-pending' }} mt-1">{{ $alert->is_read ? 'Read' : 'Unread' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ---- Earlier incidents ---- --}}
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-1"></i>Earlier Incidents of This Kind on This Device</div>
                @if($history->isEmpty())
                    <div class="card-body text-muted text-center py-4" style="font-size:0.82rem;">This is the first one.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:0.82rem;">
                            <thead><tr><th>Detected</th><th>Last Seen</th><th class="text-center">Count</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach($history as $past)
                                    <tr>
                                        <td>{{ $past->detected_at->format('d M Y, H:i') }}</td>
                                        <td class="text-muted">{{ ($past->last_seen_at ?? $past->detected_at)->format('d M Y, H:i') }}</td>
                                        <td class="text-center">{{ $past->occurrences }}</td>
                                        <td><span class="badge {{ $past->statusBadgeClass() }} text-capitalize">{{ $past->status() }}</span></td>
                                        <td class="text-end"><a href="{{ route('admin.anomaly.anomalies.show', $past) }}"><i class="bi bi-arrow-right"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            {{-- ---- Lifecycle ---- --}}
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-diagram-3 me-1"></i>Lifecycle</div>
                <div class="list-group list-group-flush" style="font-size:0.82rem;">
                    <div class="list-group-item">
                        <i class="bi bi-exclamation-circle me-1 text-warning"></i><strong>Opened</strong>
                        <div class="text-muted">{{ $anomaly->detected_at->format('d M Y, H:i') }}</div>
                    </div>
                    @if($anomaly->alerted_at)
                        <div class="list-group-item">
                            <i class="bi bi-bell me-1 text-muted"></i><strong>Farmer alerted</strong>
                            <div class="text-muted">{{ $anomaly->alerted_at->format('d M Y, H:i') }}</div>
                        </div>
                    @endif
                    @if($anomaly->acknowledged_at)
                        <div class="list-group-item">
                            <i class="bi bi-eye me-1 text-primary"></i><strong>Acknowledged</strong>
                            <div class="text-muted">{{ $anomaly->acknowledged_at->format('d M Y, H:i') }} by {{ $anomaly->acknowledgedBy->name ?? 'a removed user' }}</div>
                        </div>
                    @endif
                    @if($anomaly->resolved)
                        <div class="list-group-item">
                            <i class="bi bi-check-circle me-1 text-success"></i><strong>Resolved</strong>
                            <div class="text-muted">
                                {{ $anomaly->resolved_at?->format('d M Y, H:i') }}
                                @if($anomaly->auto_resolved)
                                    — automatically, readings returned to normal
                                @else
                                    by {{ $anomaly->resolvedBy->name ?? 'a removed user' }}
                                @endif
                            </div>
                            @if($anomaly->resolution_note)
                                <div class="mt-1 p-2 rounded" style="background:var(--clr-canvas);">{{ $anomaly->resolution_note }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- ---- Actions ---- --}}
            @unless($anomaly->resolved)
                <div class="card">
                    <div class="card-header">Actions</div>
                    <div class="card-body">
                        @unless($anomaly->acknowledged_at)
                            <form action="{{ route('admin.anomaly.anomalies.acknowledge', $anomaly) }}" method="POST" class="mb-3">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-forest w-100">
                                    <i class="bi bi-eye me-1"></i>Acknowledge
                                </button>
                                <div class="form-text">Mark that someone is looking into it.</div>
                            </form>
                        @endunless

                        <form action="{{ route('admin.anomaly.anomalies.resolve', $anomaly) }}" method="POST">
                            @csrf @method('PATCH')
                            <label for="note" class="form-label mb-1" style="font-size:0.75rem;">Resolution note (optional)</label>
                            <textarea name="note" id="note" rows="3" maxlength="1000"
                                      class="form-control form-control-sm mb-2 @error('note') is-invalid @enderror"
                                      placeholder="e.g. Replaced battery, sensor recalibrated…">{{ old('note') }}</textarea>
                            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-check-circle me-1"></i>Resolve
                            </button>
                            <div class="form-text">
                                Incidents also resolve on their own once readings are back to normal. If the problem comes back later, a new incident is opened.
                            </div>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>

@endsection
