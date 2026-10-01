{{--
    Incident rows shared by the Anomaly Dashboard and the incident list.
    Expects $anomalies, $headlines (incident id => what was seen) and $caption.
--}}

@pushOnce('styles')
    <style>
        .incident-table td { vertical-align: top; }
        .incident-table th:first-child, .incident-table td:first-child { min-width: 17rem; }
        .incident-table .incident-headline { margin-top: 0.3rem; font-size: 0.85rem; color: var(--clr-dark); max-width: 34rem; }
        .incident-table .incident-meta { margin-top: 0.15rem; font-size: 0.78rem; color: #52635A; }
        .incident-table .incident-actions { display: flex; justify-content: flex-end; gap: 0.4rem; }
        .incident-table .incident-actions .btn { white-space: nowrap; }
    </style>
@endPushOnce

<div class="table-responsive">
    <table class="table table-hover mb-0 incident-table">
        <caption class="visually-hidden">{{ $caption }}</caption>
        <thead>
            <tr>
                <th scope="col">Incident</th>
                <th scope="col">Where</th>
                <th scope="col">When</th>
                <th scope="col" class="text-center">Times seen</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($anomalies as $anomaly)
                @php
                    $hiveName = $anomaly->hive ? ($anomaly->hive->display_name ?? $anomaly->hive->hive_code) : null;
                    $subject = $anomaly->label().' on '.($hiveName ? 'hive '.$hiveName : 'device '.($anomaly->device->device_code ?? '#'.$anomaly->device_id));
                    $stream = \App\Enums\SensorMetric::tryFrom($anomaly->sensor_type)?->label();
                @endphp
                <tr>
                    <td>
                        <span class="badge {{ $anomaly->badgeClass() }}">
                            <i class="bi {{ $anomaly->icon() }} me-1" aria-hidden="true"></i>{{ $anomaly->label() }}
                        </span>
                        @if(! empty($headlines[$anomaly->id]))
                            <div class="incident-headline">{{ $headlines[$anomaly->id] }}</div>
                        @endif
                        <div class="incident-meta">{{ $anomaly->kind() }}@if($stream) · {{ $stream }}@endif</div>
                    </td>
                    <td class="text-nowrap">
                        @if($anomaly->hive)
                            <a href="{{ route('admin.hives.show', $anomaly->hive) }}" class="text-decoration-none">{{ $hiveName }}</a>
                        @else
                            <span class="text-muted">No hive</span>
                        @endif
                        <div class="incident-meta">
                            @if($anomaly->device && ! $anomaly->device->trashed())
                                <a href="{{ route('admin.iot-devices.show', $anomaly->device) }}" class="text-decoration-none">{{ $anomaly->device->device_code }}</a>
                            @else
                                {{ $anomaly->device->device_code ?? '#'.$anomaly->device_id }}
                            @endif
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <span title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">{{ $anomaly->detected_at->diffForHumans() }}</span>
                        @if($anomaly->last_seen_at && $anomaly->last_seen_at->gt($anomaly->detected_at))
                            <div class="incident-meta" title="{{ $anomaly->last_seen_at->format('Y-m-d H:i:s') }}">last seen {{ $anomaly->last_seen_at->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $anomaly->occurrences }}</td>
                    <td>
                        <span class="badge {{ $anomaly->statusBadgeClass() }} text-capitalize">{{ $anomaly->status() }}</span>
                        @if($anomaly->auto_resolved)
                            <div class="incident-meta">Cleared on its own</div>
                        @endif
                    </td>
                    <td>
                        <div class="incident-actions">
                            @if($anomaly->status() === 'open')
                                <form action="{{ route('admin.anomaly.anomalies.acknowledge', $anomaly) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-forest">
                                        Acknowledge<span class="visually-hidden"> {{ $subject }}</span>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.anomaly.anomalies.show', $anomaly) }}" class="btn btn-sm btn-primary">
                                Open<span class="visually-hidden"> {{ $subject }}</span>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
