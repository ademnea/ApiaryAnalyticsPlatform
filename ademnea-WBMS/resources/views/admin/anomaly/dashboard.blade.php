@extends('layouts.app')

@section('title', 'Anomaly Dashboard')
@section('page-title', 'Anomaly Dashboard')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Anomaly Dashboard</li>
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

        .stat-card.is-tight .stat-value { font-size: 1.3rem; }

        .panel-empty { text-align: center; color: var(--clr-muted); font-size: 0.8rem; padding: 1.85rem 1rem; }
        .panel-empty i { font-size: 1.5rem; color: var(--clr-forest-light); display: block; margin-bottom: 0.4rem; }

        .fleet-callout {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.65rem 0.9rem; margin-bottom: 1rem;
            border: 1px solid var(--clr-border); border-radius: 10px;
            background: var(--clr-canvas); font-size: 0.8rem; text-decoration: none; color: inherit;
        }
        .fleet-callout:hover { background: var(--clr-forest-pale); }
    </style>
@endpush

@section('content')

    @include('admin.anomaly._subnav')

    <div class="section-heading">
        <div>
            <h6>Hive Conditions</h6>
            <p>Threshold breaches, frozen sensors and statistical deviations in hive readings. Each row is one incident — repeats update it instead of piling up.</p>
        </div>
        <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'hive', 'status' => 'unresolved']) }}" class="meta-link">
            View all unresolved <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    {{-- ---- KPI row ---- --}}
    <div class="row g-3 mb-3">
        @foreach([
            ['Open', $openCount, 'bi-exclamation-circle', $openCount > 0 ? 'red' : 'green', ['category' => 'hive', 'status' => 'open']],
            ['Acknowledged', $acknowledgedCount, 'bi-eye', 'blue', ['category' => 'hive', 'status' => 'acknowledged']],
            ['Hives Affected', $hivesAffectedCount, 'bi-hexagon', $hivesAffectedCount > 0 ? 'honey' : 'green', ['category' => 'hive', 'status' => 'unresolved']],
            ['New Today', $newTodayCount, 'bi-calendar-event', 'honey', ['category' => 'hive', 'from' => today()->toDateString()]],
        ] as [$label, $value, $icon, $tone, $query])
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.anomaly.anomalies.index', $query) }}" class="text-decoration-none text-reset">
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

    <a href="{{ route('admin.devices.fleet') }}" class="fleet-callout">
        <i class="bi bi-grid-3x3-gap" style="font-size:1.1rem;color:var(--clr-forest);"></i>
        <span class="flex-grow-1">
            Battery, signal and connectivity problems are tracked on the <strong>Device Fleet</strong> page.
        </span>
        @if($openDeviceIssuesCount > 0)
            <span class="badge badge-offline">{{ $openDeviceIssuesCount }} open device {{ Str::plural('issue', $openDeviceIssuesCount) }}</span>
        @else
            <span class="badge badge-active">No open device issues</span>
        @endif
        <i class="bi bi-arrow-right text-muted"></i>
    </a>

    <div class="row g-3">
        {{-- ---- Latest open incidents ---- --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ul me-1"></i>Latest Unresolved</span>
                    <span class="text-muted" style="font-size:0.72rem;font-weight:400;">Most recently active 10</span>
                </div>

                @if($latestOpen->isEmpty())
                    <div class="panel-empty py-5">
                        <i class="bi bi-shield-check" style="font-size:1.8rem;"></i>
                        No unresolved hive anomalies — all hives look normal.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Anomaly</th>
                                    <th>Hive</th>
                                    <th>Since</th>
                                    <th class="text-center">Count</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($latestOpen as $anomaly)
                                    <tr>
                                        <td>
                                            <span class="badge {{ $anomaly->badgeClass() }}">
                                                <i class="bi {{ $anomaly->icon() }} me-1"></i>{{ $anomaly->label() }}
                                            </span>
                                            <div class="text-muted text-capitalize" style="font-size:0.7rem;">
                                                {{ $anomaly->sensor_type }} · {{ $anomaly->device->device_code ?? '#' . $anomaly->device_id }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($anomaly->hive)
                                                <a href="{{ route('admin.hives.show', $anomaly->hive) }}" class="text-decoration-none">{{ $anomaly->hive->display_name ?? $anomaly->hive->hive_code }}</a>
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td class="text-muted" title="{{ $anomaly->detected_at->format('Y-m-d H:i:s') }}">{{ $anomaly->detected_at->diffForHumans() }}</td>
                                        <td class="text-center">{{ $anomaly->occurrences }}</td>
                                        <td><span class="badge {{ $anomaly->statusBadgeClass() }} text-capitalize">{{ $anomaly->status() }}</span></td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('admin.anomaly.anomalies.show', $anomaly) }}" class="btn btn-sm btn-outline-forest" title="View anomaly"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">
            {{-- ---- Unresolved by type ---- --}}
            <div class="card">
                <div class="card-header"><i class="bi bi-pie-chart me-1"></i>Unresolved by Type</div>
                <div class="card-body">
                    @if($byType->isEmpty())
                        <div class="panel-empty py-3"><i class="bi bi-shield-check"></i>Nothing outstanding.</div>
                    @else
                        <canvas id="byTypeChart" height="200"></canvas>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($bySensorType as $sensor => $total)
                                <span class="badge badge-pending text-capitalize">{{ $sensor }}: {{ $total }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ---- Most affected hives ---- --}}
            <div class="card">
                <div class="card-header"><i class="bi bi-hexagon me-1"></i>Most Affected Hives</div>
                @if($mostAffectedHives->isEmpty())
                    <div class="panel-empty py-3"><i class="bi bi-hexagon"></i>No hive has an open anomaly.</div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($mostAffectedHives as $hive)
                            <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'hive', 'status' => 'unresolved', 'hive_id' => $hive->id]) }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="font-size:0.82rem;">
                                <span>
                                    {{ $hive->display_name ?? $hive->hive_code }}
                                    @if($hive->apiary)<span class="text-muted d-block" style="font-size:0.72rem;">{{ $hive->apiary->name }}</span>@endif
                                </span>
                                <span class="badge badge-offline">{{ $mostAffected[$hive->id] }} open</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@if($byType->isNotEmpty())
    @push('scripts')
        <script>
            new Chart(document.getElementById('byTypeChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($byType->keys()->map(fn ($t) => ucwords(str_replace('_', ' ', $t)))),
                    datasets: [{
                        data: @json($byType->values()),
                        backgroundColor: ['#7F1D1D', '#D4A017', '#0057b8', '#2D6A4F', '#40916C', '#664D03'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                    maintainAspectRatio: true,
                },
            });
        </script>
    @endpush
@endif
