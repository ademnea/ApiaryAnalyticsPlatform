@extends('layouts.app')

@section('title', $anomaly->label())
@section('page-title', $anomaly->label())
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.anomaly.anomalies.index', ['category' => $anomaly->category()]) }}">All Anomalies</a></li>
    <li class="breadcrumb-item active" aria-current="page">#{{ $anomaly->id }}</li>
@endsection

@push('styles')
    <style>
        .evidence-action {
            display: flex; gap: 0.65rem; padding: 0.65rem 0.85rem;
            border: 1px solid var(--clr-border); border-radius: 10px;
            background: var(--clr-canvas); font-size: 0.82rem;
        }
        .evidence-action i { color: var(--clr-forest); font-size: 1rem; }
        .evidence-dot {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%;
            background: #b30000; margin-right: 0.35rem;
        }
    </style>
@endpush

@section('content')

    @include('admin.anomaly._subnav')

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="badge {{ $anomaly->badgeClass() }}" style="font-size:0.8rem;">
            <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
        </span>
        <span class="badge {{ $anomaly->statusBadgeClass() }} text-capitalize" style="font-size:0.8rem;">{{ $anomaly->status() }}</span>
        <span class="badge badge-pending text-capitalize" style="font-size:0.8rem;">{{ $anomaly->severity() }}</span>
        <span class="text-muted" style="font-size:0.8rem;">
            <i class="bi {{ $anomaly->kindIcon() }} me-1"></i>{{ $anomaly->kind() }}
        </span>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- ---- What happened ---- --}}
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-search me-1"></i>What Happened</div>
                <div class="card-body">
                    <p class="mb-3" style="font-size:0.9rem;">{{ $explanation }}</p>

                    @if($chart)
                        <div style="position:relative;height:240px;">
                            <canvas id="evidenceChart" role="img"
                                    aria-label="Line chart of {{ Str::lower($chart['series']) }} readings around this incident, with flagged readings marked"></canvas>
                        </div>
                        <p class="text-muted mt-2 mb-3" style="font-size:0.74rem;">
                            <span class="evidence-dot"></span>Red points are readings marked suspect, or the reading that opened the incident.
                            @if($chart['band'])The shaded area is the {{ Str::lcfirst($chart['band']['label']) }}.@endif
                        </p>
                    @elseif($anomaly->isDeviceIssue() && $anomaly->device && ! $anomaly->device->trashed())
                        <p class="mb-3" style="font-size:0.8rem;">
                            <a href="{{ route('admin.iot-devices.show', $anomaly->device) }}">See this device's battery, signal and submission trends <i class="bi bi-arrow-right"></i></a>
                        </p>
                    @endif

                    <div class="evidence-action">
                        <i class="bi bi-tools" aria-hidden="true"></i>
                        <div>
                            <strong>Recommended action</strong>
                            <div>{{ $recommendedAction }}</div>
                        </div>
                    </div>

                    <p class="mt-3 mb-0" style="font-size:0.82rem;">
                        A false alarm?
                        <a href="{{ route('admin.anomaly.limits', array_filter(['hive_id' => $anomaly->hive_id])) }}#rule-{{ $limitRule }}">
                            Review the limit{{ $anomaly->hive_id ? ' for this hive' : '' }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </p>
                </div>
            </div>

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
                            @php $lastSeen = $anomaly->last_seen_at ?? $anomaly->detected_at; @endphp
                            {{ $lastSeen->format('d M Y, H:i:s') }} <span class="text-muted">({{ $lastSeen->diffForHumans() }})</span>
                        </dd>

                        <dt class="col-sm-4 text-muted">Occurrences</dt>
                        <dd class="col-sm-8">{{ $anomaly->occurrences }}</dd>

                        <dt class="col-sm-4 text-muted">First Flagged Value</dt>
                        <dd class="col-sm-8"><code>{{ \App\Models\SensorAnomaly::formatValues($anomaly->record_value) }}</code></dd>

                        <dt class="col-sm-4 text-muted">Latest Value</dt>
                        <dd class="col-sm-8"><code>{{ \App\Models\SensorAnomaly::formatValues($anomaly->last_record_value ?? $anomaly->record_value) }}</code></dd>
                    </dl>
                </div>
            </div>

            {{-- ---- Notifications ---- --}}
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-bell me-1"></i>Notifications</span>
                    <span class="text-muted" style="font-size:0.72rem;font-weight:400;">
                        @forelse($notificationSummary['routes'] as $route)
                            {{ $route['recipient'] }} ({{ implode(', ', $route['channels']) }})@if(! $loop->last) · @endif
                        @empty
                            Dashboard only
                        @endforelse
                    </span>
                </div>

                @if($notificationSummary['emptyReason'])
                    <div class="card-body text-muted text-center py-4" style="font-size:0.82rem;">{{ $notificationSummary['emptyReason'] }}</div>
                @else
                    <div class="list-group list-group-flush" style="font-size:0.82rem;">
                        @foreach($anomaly->alerts as $alert)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <span>
                                        <i class="bi bi-phone me-1 text-muted"></i>
                                        <strong>{{ $alert->farmer ? trim($alert->farmer->first_name . ' ' . $alert->farmer->last_name) : 'Farmer #' . $alert->farmer_id }}</strong>
                                        <span class="text-muted">· in the farmer app</span>
                                    </span>
                                    <span class="text-muted" title="{{ $alert->created_at?->format('Y-m-d H:i:s') }}">{{ $alert->created_at?->diffForHumans() }}</span>
                                </div>
                                <div class="text-muted mt-1">{{ $alert->message }}</div>
                                <span class="badge {{ $alert->is_read ? 'badge-active' : 'badge-pending' }} mt-1">{{ $alert->is_read ? 'Read' : 'Unread' }}</span>
                            </div>
                        @endforeach

                        @foreach($anomaly->notifications as $notification)
                            @php
                                $channelIcon = ['email' => 'bi-envelope', 'sms' => 'bi-chat-dots', 'push' => 'bi-bell'][$notification->channel] ?? 'bi-send';
                                $statusBadge = ['sent' => 'badge-active', 'failed' => 'badge-offline', 'skipped' => 'badge-pending'][$notification->status] ?? 'badge-info';
                                $recipient = match ($notification->recipient_type) {
                                    'admin' => 'Admin',
                                    'hardware_team' => 'Hardware team',
                                    default => $notification->farmer ? trim($notification->farmer->first_name . ' ' . $notification->farmer->last_name) : 'Farmer',
                                };
                                $when = $notification->sent_at ?? $notification->created_at;
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <span>
                                        <i class="bi {{ $channelIcon }} me-1 text-muted"></i>
                                        <strong>{{ $recipient }}</strong>
                                        {{-- A farmer's recipient is an FCM token, which means nothing to a reader. --}}
                                        @if($notification->recipient_type !== 'farmer' && $notification->recipient)
                                            <span class="text-muted">· {{ $notification->recipient }}</span>
                                        @endif
                                        <span class="text-muted">· {{ $notification->channel === 'sms' ? 'SMS' : $notification->channel }}</span>
                                    </span>
                                    <span class="text-muted" title="{{ $when?->format('Y-m-d H:i:s') }}">{{ $when?->diffForHumans() }}</span>
                                </div>
                                @if($notification->subject)<div class="text-muted mt-1">{{ $notification->subject }}</div>@endif
                                <span class="badge {{ $statusBadge }} text-capitalize mt-1">{{ $notification->status }}</span>
                                @if($notification->error_message && $notification->status !== 'sent')
                                    <span class="text-muted ms-1">{{ $notification->error_message }}</span>
                                @endif
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
                            <i class="bi bi-bell me-1 text-muted"></i><strong>Notifications sent</strong>
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

@if($chart)
    @include('admin.monitoring._chart-helpers')

    @push('scripts')
        <script>
            (function () {
                const chart = @json($chart);
                const { format, bandDatasets } = window.MonitoringCharts;
                const flaggedColor = '#b30000';

                const datasets = [{
                    label: chart.series,
                    data: chart.values,
                    borderColor: chart.color,
                    backgroundColor: chart.color,
                    borderWidth: 2,
                    tension: 0.2,
                    fill: false,
                    pointRadius: chart.flagged.map((flagged) => flagged ? 4 : (chart.values.length <= 48 ? 2 : 0)),
                    pointHoverRadius: 5,
                    pointBackgroundColor: chart.flagged.map((flagged) => flagged ? flaggedColor : chart.color),
                    pointBorderColor: chart.flagged.map((flagged) => flagged ? flaggedColor : chart.color),
                }];

                if (chart.band) {
                    datasets.push(...bandDatasets(chart.band, chart.values.length, '#D4A017'));
                }

                new Chart(document.getElementById('evidenceChart'), {
                    type: 'line',
                    data: { labels: chart.labels, datasets },
                    options: {
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 8, maxRotation: 0, font: { size: 11 } } },
                            y: { ticks: { font: { size: 11 }, callback: (value) => format(value, chart.unit, chart.precision) } },
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 }, filter: (item) => !item.text.endsWith('(max)') },
                            },
                            tooltip: {
                                filter: (item) => item.dataset.role !== 'band',
                                callbacks: {
                                    label: (item) => `${item.dataset.label}: ${format(item.parsed.y, chart.unit, chart.precision)}`
                                        + (chart.flagged[item.dataIndex] ? ' (flagged)' : ''),
                                },
                            },
                        },
                    },
                });
            })();
        </script>
    @endpush
@endif
