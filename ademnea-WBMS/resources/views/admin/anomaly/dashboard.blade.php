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

        .rule-card .rule-kind { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--clr-muted); margin-bottom: 0.35rem; }
        .rule-card .rule-title { font-size: 0.92rem; font-weight: 700; color: var(--clr-forest); }
        .rule-card .rule-desc { font-size: 0.8rem; margin: 0.35rem 0 0.6rem; }
        .rule-card .rule-limits { list-style: none; padding: 0; margin: 0 0 0.6rem; font-size: 0.78rem; }
        .rule-card .rule-limits li { padding: 0.15rem 0; border-top: 1px dashed var(--clr-border); }
        .rule-card .rule-limits li:first-child { border-top: 0; }
        .rule-card .rule-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--clr-muted); }
        .rule-card .rule-notifies { font-size: 0.76rem; color: var(--clr-muted); }
        .rule-card .card-footer { font-size: 0.78rem; background: var(--clr-canvas); }
        .rule-card .card-footer a { color: var(--clr-forest-mid); text-decoration: none; }
        .rule-card .card-footer a:hover { text-decoration: underline; }

        .attention-hives { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1.25rem; font-size: 0.85rem; background: var(--clr-canvas); }
        .attention-hives-label { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #52635A; }
        .attention-hives a { color: var(--clr-forest); text-decoration: none; font-weight: 600; }
        .attention-hives a:hover { text-decoration: underline; }

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
            <p>Unresolved incidents in hive readings. Acknowledge one to show someone is on it; it closes by itself once readings are back to normal.</p>
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

    <div class="row g-3">
        {{-- ---- Latest open incidents ---- --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ul me-1" aria-hidden="true"></i>Needs Attention</span>
                    <span class="text-muted" style="font-size:0.78rem;font-weight:400;">The 10 most recently active</span>
                </div>

                @if($latestOpen->isEmpty())
                    <div class="panel-empty py-5">
                        <i class="bi bi-shield-check" style="font-size:1.8rem;"></i>
                        No unresolved hive anomalies — all hives look normal.
                    </div>
                @else
                    @include('admin.anomaly._incident-table', ['anomalies' => $latestOpen, 'caption' => 'The ten most recently active unresolved hive incidents'])

                    <div class="card-footer attention-hives">
                        <span class="attention-hives-label">Most affected hives</span>
                        @foreach($mostAffectedHives as $hive)
                            <a href="{{ route('admin.anomaly.anomalies.index', ['category' => 'hive', 'status' => 'unresolved', 'hive_id' => $hive->id]) }}">
                                {{ $hive->display_name ?? $hive->hive_code }}
                                <span class="badge badge-offline">{{ $mostAffected[$hive->id] }} unresolved</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-3">
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

    </div>

    <div class="section-heading mt-4">
        <div>
            <h6>How Readings Are Checked</h6>
            <p>Three rules run on every reading as it arrives. A sensor fault means the hardware is wrong; a colony signal may be a real event in the hive.</p>
        </div>
        <a href="{{ route('admin.anomaly.limits') }}" class="meta-link">Change limits <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>

    {{-- ---- The rules, each tagged with what a violation means ---- --}}
    <div class="row g-3 mb-3">
        @foreach($ruleGroups as $group)
            @foreach($group['rules'] as $rule)
                <div class="col-md-6 col-lg-4">
                    <div class="card rule-card h-100">
                        <div class="card-body">
                            <div class="rule-kind" title="{{ $group['summary'] }}">
                                <i class="bi {{ $group['icon'] }} me-1" aria-hidden="true"></i>{{ Str::singular($group['title']) }}
                            </div>
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span class="rule-title"><i class="bi {{ $rule['icon'] }} me-1" aria-hidden="true"></i>{{ $rule['label'] }}</span>
                                @if($rule['openCount'] > 0)
                                    <span class="badge badge-warning">{{ $rule['openCount'] }} unresolved</span>
                                @else
                                    <span class="badge badge-active">All clear</span>
                                @endif
                            </div>
                            <p class="rule-desc">{{ $rule['description'] }}</p>

                            <div class="rule-label">Flagged when</div>
                            <ul class="rule-limits">
                                @foreach($rule['limits'] as $limit)
                                    <li>{{ $limit }}</li>
                                @endforeach
                            </ul>

                            <div class="rule-label">Notifies</div>
                            <div class="rule-notifies">
                                @forelse($rule['notifies'] as $route)
                                    {{ $route['recipient'] }} ({{ implode(', ', $route['channels']) }})@if(! $loop->last) · @endif
                                @empty
                                    Nobody. Dashboard only.
                                @endforelse
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between gap-2">
                            <a href="{{ route('admin.anomaly.anomalies.index', array_filter(['category' => 'hive', 'status' => $rule['openCount'] > 0 ? 'unresolved' : null, 'type' => $rule['type']])) }}">
                                @if($rule['openCount'] > 0)
                                    View {{ $rule['openCount'] }} in {{ $rule['hivesAffected'] }} {{ Str::plural('hive', $rule['hivesAffected']) }}
                                @else
                                    View history
                                @endif
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('admin.anomaly.limits') }}#rule-{{ $rule['type'] }}"><i class="bi bi-sliders me-1" aria-hidden="true"></i>Change limits</a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

@endsection
