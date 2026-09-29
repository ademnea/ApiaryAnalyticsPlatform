@extends('admin.monitoring.layout')

{{-- Module landing page: is every hive reporting every stream? --}}

@php
    use App\Enums\SensorMetric;
    use App\Services\Monitoring\MonitoringService;
    use Illuminate\Support\Arr;

    $query = $filters->queryString();
    $streamCount = count(SensorMetric::cases());
    $liveStreams = collect($sensorSnapshot)
        ->reject(fn (array $snapshot) => MonitoringService::isStale($snapshot['last_reading_at'], $staleAfterMinutes))
        ->count();
@endphp

@section('monitoring')
    @include('admin.monitoring._chart-helpers')

    {{-- ---- KPI row ---- --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon green"><i class="bi bi-database" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ number_format(collect($sensorSnapshot)->sum('readings')) }}</div>
                    <div class="stat-label">Sensor Readings</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon blue"><i class="bi bi-collection-play" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ number_format(collect($mediaSnapshot)->sum('files')) }}</div>
                    <div class="stat-label">Media Captures</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon {{ $anomalyCount > 0 ? 'red' : 'green' }}"><i class="bi bi-shield-exclamation" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ number_format($anomalyCount) }}</div>
                    <div class="stat-label">Open {{ Str::plural('Anomaly', $anomalyCount) }}</div>
                    @can('view-anomaly-analytics')
                        @if($anomalyCount > 0)
                            <a href="{{ route('admin.anomaly.dashboard') }}" class="stat-sub d-inline-block">Review <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon {{ $liveStreams < $streamCount ? 'honey' : 'green' }}"><i class="bi bi-broadcast" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ $liveStreams }}<span class="text-muted fs-6 fw-normal"> / {{ $streamCount }}</span></div>
                    <div class="stat-label">Sensor Streams Live</div>
                    <div class="stat-sub">Stale after {{ $staleAfterMinutes }} min of silence</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ---- One card per sensor stream ---- --}}
    <div class="row g-3 mb-3">
        @foreach($sensorSnapshot as $snapshot)
            @php
                $metric = $snapshot['metric'];
            @endphp
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="metric-icon"><i class="bi {{ $metric->icon() }}" aria-hidden="true"></i></span>
                                <h3 class="metric-title mb-0">{{ $metric->label() }}</h3>
                            </div>
                            @include('admin.monitoring._freshness', ['lastAt' => $snapshot['last_reading_at']])
                        </div>
                        <div class="metric-value tabular">
                            {{ $snapshot['average'] === null ? '—' : number_format($snapshot['average'], $metric->precision()) }}
                            <span class="metric-unit">{{ $metric->unit() }}</span>
                        </div>
                        <div class="text-muted monitoring-hint">
                            {{ $metric->isMultiZone() ? $metric->columns()[$metric->primaryColumn()].' average' : 'Average' }}
                        </div>
                        @if($snapshot['readings'] > 0)
                            @php
                                // Built here because the json directive splits its argument on commas.
                                $sparkPayload = ['values' => $snapshot['spark'], 'color' => $metric->streamColor(), 'unit' => $metric->unit(), 'precision' => $metric->precision()];
                            @endphp
                            <div class="sparkline-wrap">
                                <canvas id="spark-{{ $metric->value }}" role="img"
                                        aria-label="Trend of {{ Str::lower($metric->label()) }} over {{ Str::lower($filters->windowLabel()) }}"></canvas>
                            </div>
                            <script type="application/json" data-sparkline="{{ $metric->value }}">@json($sparkPayload)</script>
                        @endif
                        <dl class="metric-facts">
                            <div><dt>Readings</dt><dd class="tabular">{{ number_format($snapshot['readings']) }}</dd></div>
                            <div><dt>Hives</dt><dd class="tabular">{{ number_format($snapshot['hives_reporting']) }}</dd></div>
                            <div><dt>Last</dt><dd class="tabular">{{ $snapshot['last_reading_at']?->diffForHumans(['short' => true, 'parts' => 1]) ?? '—' }}</dd></div>
                        </dl>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="{{ route($metric->routeName(), $query) }}" class="stretched-link text-decoration-none fw-medium monitoring-hint">
                            Open details<span class="visually-hidden"> for {{ $metric->label() }}</span>
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ---- Data volume ---- --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-bar-chart-steps me-1" aria-hidden="true"></i>Readings Received</span>
            <span class="text-muted fw-normal monitoring-hint">Per {{ $filters->isHourly() ? 'hour' : 'day' }}, by the time each reading was taken</span>
        </div>
        <div class="card-body">
            @if($volume['has_data'])
                <div class="chart-wrap chart-wrap--short">
                    <canvas id="volumeChart" role="img" aria-label="Stacked bar chart of sensor readings per {{ $filters->isHourly() ? 'hour' : 'day' }} for each stream."></canvas>
                </div>
                <script type="application/json" data-volume-chart>@json(['volume' => $volume])</script>
                <p class="text-muted monitoring-hint mt-2 mb-0">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    A gap in one colour is that sensor failing; a gap across every colour is a device, power or network outage.
                </p>
            @else
                <p class="text-muted monitoring-hint text-center py-4 mb-0" role="status">No sensor readings in this window.</p>
            @endif
        </div>
    </div>

    {{-- ---- Media captures ---- --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-collection-play me-1" aria-hidden="true"></i>Media Captures</div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($mediaSnapshot as $snapshot)
                    @php
                        $kind = $snapshot['kind'];
                    @endphp
                    <div class="col-12 col-md-4">
                        <a href="{{ route($kind->routeName(), $query) }}" class="d-flex align-items-center gap-3 p-2 rounded text-decoration-none text-body border h-100">
                            <span class="stat-icon green"><i class="bi {{ $kind->icon() }}" aria-hidden="true"></i></span>
                            <span>
                                <span class="d-block fw-semibold tabular">{{ number_format($snapshot['files']) }} {{ Str::plural($kind->singular(), $snapshot['files']) }}</span>
                                <span class="d-block text-muted monitoring-hint">
                                    {{ $snapshot['last_capture_at'] ? 'Latest '.$snapshot['last_capture_at']->diffForHumans() : 'None in this window' }}
                                </span>
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ---- Hive coverage ---- --}}
    <div class="card" id="coverage">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-grid-3x3-gap me-1" aria-hidden="true"></i>Hive Reporting Coverage</span>
            <span class="text-muted fw-normal monitoring-hint">Last reading per stream within the selected window</span>
        </div>

        @if($hives->isEmpty())
            <div class="text-center text-muted py-5 px-3" role="status">
                <i class="bi bi-hexagon d-block mb-2 monitoring-empty-icon" aria-hidden="true"></i>
                <p class="fw-medium text-body mb-1">No hives found</p>
                <p class="mb-0 monitoring-hint">
                    {{ $filters->hasAnyScope() ? 'No hives match these filters.' : 'Register hives and assign devices to them to start monitoring.' }}
                </p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">When each hive last reported each sensor stream. Green dots are live, amber dots are stale.</caption>
                    <thead>
                        <tr>
                            <th scope="col">Hive</th>
                            <th scope="col">Apiary</th>
                            @foreach(SensorMetric::cases() as $metric)
                                <th scope="col" class="text-nowrap"><i class="bi {{ $metric->icon() }} me-1" aria-hidden="true"></i>{{ $metric->label() }}</th>
                            @endforeach
                            <th scope="col">Coverage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hives as $hive)
                            @php
                                $lastByMetric = collect(SensorMetric::cases())->mapWithKeys(fn ($m) => [$m->value => $coverage->get($m->value)?->get($hive->id)]);
                                $reporting = $lastByMetric->filter()->count();
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.hives.show', $hive) }}" class="fw-medium text-decoration-none">{{ $hive->display_name ?: $hive->hive_code }}</a>
                                    @if($hive->display_name && $hive->hive_code)
                                        <div class="text-muted monitoring-hint">{{ $hive->hive_code }}</div>
                                    @endif
                                </td>
                                <td>{{ $hive->apiary?->name ?? '—' }}</td>
                                @foreach(SensorMetric::cases() as $metric)
                                    <td class="text-nowrap">
                                        @if($lastByMetric[$metric->value])
                                            <a href="{{ route($metric->routeName(), ['hive_id' => $hive->id] + Arr::except($query, ['apiary_id', 'hive_id'])) }}" class="text-decoration-none text-body">
                                                @include('admin.monitoring._freshness', ['lastAt' => $lastByMetric[$metric->value], 'variant' => 'dot'])
                                            </a>
                                        @else
                                            @include('admin.monitoring._freshness', ['lastAt' => null, 'variant' => 'dot'])
                                        @endif
                                    </td>
                                @endforeach
                                <td>
                                    @if($reporting === $streamCount)
                                        <span class="badge badge-active">All streams</span>
                                    @elseif($reporting === 0)
                                        <span class="badge badge-offline">Silent</span>
                                    @else
                                        <span class="badge badge-warning">{{ $reporting }} of {{ $streamCount }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($hives->hasPages())
                <div class="card-footer bg-white">{{ $hives->links() }}</div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const { withAlpha, format, payload, mount } = window.MonitoringCharts;

            // ---- Sparklines: shape only, value on hover ---------------------
            @foreach(SensorMetric::cases() as $metric)
                mount('spark-{{ $metric->value }}', () => {
                    const spark = payload('script[data-sparkline="{{ $metric->value }}"]');

                    if (!spark) {
                        return null;
                    }

                    return {
                        type: 'line',
                        data: {
                            labels: payload('script[data-volume-chart]')?.volume.labels ?? spark.values.map(() => ''),
                            datasets: [{ data: spark.values, borderColor: spark.color, backgroundColor: withAlpha(spark.color, 0.12), borderWidth: 1.5, pointRadius: 0, pointHoverRadius: 3, tension: 0.3, fill: true, spanGaps: false }],
                        },
                        options: {
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            scales: { x: { display: false }, y: { display: false, grace: '10%' } },
                            plugins: {
                                legend: { display: false },
                                tooltip: { displayColors: false, filter: (item) => item.raw !== null, callbacks: { label: (context) => format(context.raw, spark.unit, spark.precision) } },
                            },
                        },
                    };
                });
            @endforeach

            // ---- Readings received per bucket, stacked by stream ------------
            mount('volumeChart', () => {
                const data = payload('script[data-volume-chart]')?.volume;

                if (!data) {
                    return null;
                }

                return {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: data.series.map((series) => ({ label: series.label, data: series.counts, backgroundColor: withAlpha(series.color, 0.85), borderWidth: 0, barPercentage: 1, categoryPercentage: 0.9 })),
                    },
                    options: {
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { stacked: true, grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 8, maxRotation: 0, font: { size: 11 } } },
                            y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(27, 67, 50, 0.06)' }, ticks: { precision: 0, font: { size: 11 } } },
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } },
                        },
                    },
                };
            });
        })();
    </script>
@endpush
