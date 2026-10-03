@extends('admin.monitoring.layout')

{{-- One page for temperature, humidity, CO₂ and weight; $metric decides which. --}}

@php
    use App\Services\Monitoring\MonitoringFilters;
    use App\Services\Monitoring\MonitoringService;
    use Illuminate\Support\Arr;

    $unit = $metric->unit();
    $number = fn (?float $value) => $value === null ? '—' : number_format($value, $metric->precision());
    [$boundMin, $boundMax] = [$chart['bounds']['min'], $chart['bounds']['max']];
    $band = $chart['band'];
    $lastReadingAt = $summary['last_reading_at'];
    $lastReceivedAt = $summary['last_received_at'];
    $isBacklog = MonitoringService::isBacklog($lastReadingAt, $lastReceivedAt, $staleAfterMinutes);
    $clearUrl = $filters->hasAnyScope() ? route($metric->routeName()) : null;
    $liveTableUrl = route($metric->routeName(), $filters->queryString()).'#readings';

    // Built here because the json directive splits its argument on commas.
    $chartPayload = ['chart' => $chart, 'unit' => $unit, 'precision' => $metric->precision()];
@endphp

@section('monitoring')
    @include('admin.monitoring._chart-helpers')

    {{-- ---- KPI row ---- --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon green"><i class="bi bi-database" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ number_format($summary['readings']) }}</div>
                    <div class="stat-label">{{ Str::plural('Reading', $summary['readings']) }}</div>
                    @if($summary['flagged'] > 0)
                        <div class="stat-sub"><i class="bi bi-flag-fill text-warning me-1" aria-hidden="true"></i>{{ number_format($summary['flagged']) }} flagged</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon blue"><i class="bi bi-hexagon" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ number_format($summary['hives_reporting']) }}</div>
                    <div class="stat-label">{{ Str::plural('Hive', $summary['hives_reporting']) }} Reporting</div>
                    <div class="stat-sub">{{ number_format($summary['devices_reporting']) }} {{ Str::plural('device', $summary['devices_reporting']) }}</div>
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
                            <a href="{{ route('admin.anomaly.dashboard') }}" class="stat-sub d-inline-block">Review in Anomaly Dashboard <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon {{ match (true) { $lastReadingAt === null => 'red', $isBacklog => 'blue', MonitoringService::isStale($lastReadingAt, $staleAfterMinutes) => 'honey', default => 'green' } }}">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="stat-value tabular">
                        @if($lastReadingAt)
                            <time datetime="{{ $lastReadingAt->toIso8601String() }}" title="{{ $lastReadingAt->format('Y-m-d H:i:s T') }}">{{ $lastReadingAt->diffForHumans(['short' => true, 'parts' => 1]) }}</time>
                        @else
                            —
                        @endif
                    </div>
                    <div class="stat-label">Last Reading</div>
                    <div class="stat-sub">@include('admin.monitoring._freshness', ['lastAt' => $lastReadingAt, 'receivedAt' => $lastReceivedAt])</div>
                    @if($lastReceivedAt)
                        <div class="stat-sub">
                            Received <time datetime="{{ $lastReceivedAt->toIso8601String() }}" title="{{ $lastReceivedAt->format('Y-m-d H:i:s T') }}">{{ $lastReceivedAt->diffForHumans() }}</time>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- ---- Trend chart ---- --}}
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-graph-up me-1" aria-hidden="true"></i>{{ $metric->label() }} Trend</span>
                    @if($chart['has_data'])
                        <span class="text-muted fw-normal monitoring-hint">Averaged into 1-{{ $filters->isHourly() ? 'hour' : 'day' }} buckets</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($chart['has_data'])
                        <div class="chart-wrap">
                            <canvas id="sensorTrendChart" role="img"
                                    aria-label="Line chart of average {{ Str::lower($metric->label()) }} for {{ Str::lower($filters->windowLabel()) }}. The readings table below lists every value."></canvas>
                        </div>
                        <script type="application/json" data-sensor-chart>@json($chartPayload)</script>
                        <p class="text-muted monitoring-hint mt-2 mb-0">
                            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                            Lines are averages and shaded areas span the lowest to highest reading.
                            @if($band)
                                <span class="band-swatch" aria-hidden="true"></span>The honey band is the healthy brood chamber range ({{ $number($band['min']) }} to {{ $number($band['max']) }} {{ $unit }}); a brood line that leaves it is a colony signal, not a sensor fault.
                            @endif
                            Dashed red lines mark the plausible range ({{ $number($boundMin) }} to {{ $number($boundMax) }} {{ $unit }}) and appear only when readings approach it.
                            Gaps mean no readings arrived.
                            @if($metric->isMultiZone()) Click a zone in the legend to hide or show it. @endif
                        </p>
                    @else
                        <div class="text-center text-muted py-5 px-3" role="status">
                            <i class="bi {{ $metric->icon() }} d-block mb-2 monitoring-empty-icon" aria-hidden="true"></i>
                            <p class="fw-medium text-body mb-1">Nothing to chart yet</p>
                            <p class="mb-0 monitoring-hint">
                                {{ $clearUrl ? 'No readings match these filters. Try a wider window or clear the filters.' : 'No '.Str::lower($metric->label()).' readings have arrived in this window.' }}
                            </p>
                            @if($clearUrl)
                                <a href="{{ $clearUrl }}" class="btn btn-sm btn-outline-forest mt-3">Clear filters</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ---- Per-zone statistics ---- --}}
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-layers me-1" aria-hidden="true"></i>{{ $metric->isMultiZone() ? 'Zone Statistics' : 'Reading Statistics' }}</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <caption class="visually-hidden">Latest, lowest, average and highest {{ Str::lower($metric->label()) }} in {{ $unit }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ $metric->isMultiZone() ? 'Zone' : 'Sensor' }}</th>
                                <th scope="col" class="text-end">Latest</th>
                                <th scope="col" class="text-end">Min</th>
                                <th scope="col" class="text-end">Avg</th>
                                <th scope="col" class="text-end">Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['columns'] as $stat)
                                <tr>
                                    <th scope="row" class="row-label fw-medium text-body text-nowrap">
                                        <span class="zone-swatch" style="background: {{ $stat['color'] }}" aria-hidden="true"></span>{{ $stat['label'] }}
                                    </th>
                                    <td class="text-end tabular fw-semibold">{{ $number($stat['latest']) }}</td>
                                    <td class="text-end tabular">{{ $number($stat['min']) }}</td>
                                    <td class="text-end tabular">{{ $number($stat['avg']) }}</td>
                                    <td class="text-end tabular">{{ $number($stat['max']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-muted monitoring-hint">
                    All values in {{ $unit }}. Plausible range: {{ $number($boundMin) }} to {{ $number($boundMax) }} {{ $unit }}@if($filters->hiveId === null) (fleet default)@endif.
                </div>
            </div>
        </div>
    </div>

    {{-- ---- Weight: daily net change ---- --}}
    @if($weightChange)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-bar-chart me-1" aria-hidden="true"></i>Daily Weight Change</span>
                @if($weightChange['has_data'])
                    <span class="text-muted fw-normal monitoring-hint">
                        {{ $weightChange['hives'] > 1 ? 'Average per hive across '.$weightChange['hives'].' hives' : 'Day-average weight compared with the day before' }}
                    </span>
                @endif
            </div>
            <div class="card-body">
                @if($weightChange['has_data'])
                    <div class="chart-wrap chart-wrap--short">
                        <canvas id="weightChangeChart" role="img" aria-label="Bar chart of daily hive weight change in kilograms for {{ Str::lower($filters->windowLabel()) }}."></canvas>
                    </div>
                    <script type="application/json" data-weight-change>@json($weightChange)</script>
                    <p class="text-muted monitoring-hint mt-2 mb-0">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        Green bars are gains (nectar coming in), red bars are losses (the colony eating its stores).
                        A sudden loss of a few kilograms in one day usually means a swarm or a harvest.
                    </p>
                @else
                    <p class="text-muted monitoring-hint text-center py-4 mb-0" role="status">At least two days of weight readings are needed to show a change.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- ---- Latest reading from each device ---- --}}
    @if($latestByDevice->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-broadcast-pin me-1" aria-hidden="true"></i>Latest by Device</span>
                <span class="text-muted fw-normal monitoring-hint">
                    Newest reading from each device in this window
                    @if($latestByDevice->count() >= MonitoringService::LATEST_DEVICE_LIMIT)
                        · first {{ MonitoringService::LATEST_DEVICE_LIMIT }} shown, choose an apiary or hive to narrow
                    @endif
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">The newest {{ Str::lower($metric->label()) }} reading from each device, with when it was taken and when the server received it</caption>
                    <thead>
                        <tr>
                            <th scope="col">Device</th>
                            <th scope="col">Hive</th>
                            @foreach($metric->columns() as $label)
                                <th scope="col" class="text-end text-nowrap">{{ $label }} <span class="unit-suffix">({{ $unit }})</span></th>
                            @endforeach
                            <th scope="col">Last Recorded</th>
                            <th scope="col">Last Received</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestByDevice as $reading)
                            <tr>
                                <td class="text-nowrap">
                                    @include('admin.monitoring._device-link', ['reading' => $reading])
                                    <div class="text-muted monitoring-hint">{{ number_format($reading->readings_count) }} {{ Str::plural('reading', $reading->readings_count) }}</div>
                                </td>
                                <td>
                                    @if($reading->hive)
                                        <a href="{{ route('admin.hives.show', $reading->hive) }}" class="text-decoration-none">{{ $reading->hive->display_name ?: $reading->hive->hive_code }}</a>
                                        <div class="text-muted monitoring-hint">{{ $reading->hive->apiary?->name ?? '—' }}</div>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                @foreach(array_keys($metric->columns()) as $column)
                                    <td class="text-end tabular">{{ $number($reading->{$column}) }}</td>
                                @endforeach
                                <td class="text-nowrap">
                                    <time datetime="{{ $reading->recorded_at->toIso8601String() }}" title="{{ $reading->recorded_at->format('Y-m-d H:i:s T') }}">{{ $reading->recorded_at->diffForHumans() }}</time>
                                </td>
                                <td class="text-nowrap">
                                    @if($reading->last_received_at)
                                        <time datetime="{{ $reading->last_received_at->toIso8601String() }}" title="{{ $reading->last_received_at->format('Y-m-d H:i:s T') }}">{{ $reading->last_received_at->diffForHumans() }}</time>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>@include('admin.monitoring._freshness', ['lastAt' => $reading->recorded_at, 'receivedAt' => $reading->last_received_at])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ---- Raw readings ---- --}}
    <div class="card" id="readings">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-table me-1" aria-hidden="true"></i>Readings</span>

            {{-- Table preferences. The window, scope and flagged switch come along as hidden fields. --}}
            <form method="GET" action="{{ route($metric->routeName()) }}#readings" class="d-flex align-items-center flex-wrap gap-2 fw-normal">
                @foreach(Arr::except($filters->queryString(), ['per_page', 'sort']) as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label for="readings-sort" class="monitoring-hint text-muted mb-0">Newest by</label>
                <select id="readings-sort" name="sort" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    <option value="recorded" @selected($filters->sort === 'recorded')>Recorded (device time)</option>
                    <option value="received" @selected($filters->sort === 'received')>Received (server time)</option>
                </select>
                <label for="readings-per-page" class="monitoring-hint text-muted mb-0">Rows</label>
                <select id="readings-per-page" name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.requestSubmit()">
                    @foreach(MonitoringFilters::PER_PAGE_OPTIONS as $option)
                        <option value="{{ $option }}" @selected($filters->perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-sm btn-outline-forest">Apply</button></noscript>
            </form>
        </div>

        @if($newSinceSnapshot > 0)
            <div class="alert alert-light border-0 border-bottom rounded-0 mb-0 py-2 monitoring-hint d-flex flex-wrap align-items-center gap-2" role="status">
                <i class="bi bi-arrow-up-circle text-success" aria-hidden="true"></i>
                <span>{{ number_format($newSinceSnapshot) }} new {{ Str::plural('reading', $newSinceSnapshot) }} arrived since this list was opened. The pages below stay as they were so rows don't jump while you read.</span>
                <a href="{{ $liveTableUrl }}" class="fw-medium">Show latest</a>
            </div>
        @endif

        @if($readings->isEmpty())
            <div class="text-center text-muted py-5 px-3" role="status">
                <i class="bi bi-inbox d-block mb-2 monitoring-empty-icon" aria-hidden="true"></i>
                <p class="fw-medium text-body mb-1">No readings to list</p>
                <p class="mb-0 monitoring-hint">
                    @if($readings->currentPage() > 1)
                        This page is past the end of the list. <a href="{{ $liveTableUrl }}">Go to the first page</a>.
                    @else
                        {{ $filters->flaggedOnly ? 'No flagged readings in this window.' : 'Readings appear here as soon as devices report.' }}
                    @endif
                </p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">Individual {{ Str::lower($metric->label()) }} readings, newest {{ $filters->sort === 'received' ? 'received' : 'recorded' }} first</caption>
                    <thead>
                        <tr>
                            <th scope="col" @if($filters->sort === 'recorded') aria-sort="descending" @endif>Recorded</th>
                            <th scope="col" @if($filters->sort === 'received') aria-sort="descending" @endif>Received</th>
                            <th scope="col">Hive</th>
                            <th scope="col">Device</th>
                            @foreach($metric->columns() as $label)
                                <th scope="col" class="text-end text-nowrap">{{ $label }} <span class="unit-suffix">({{ $unit }})</span></th>
                            @endforeach
                            <th scope="col">Quality</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readings as $reading)
                            @php
                                $delay = MonitoringService::deliveryDelay($reading->recorded_at, $reading->created_at);
                            @endphp
                            <tr>
                                <td class="text-nowrap">
                                    <time datetime="{{ $reading->recorded_at->toIso8601String() }}" title="{{ $reading->recorded_at->format('Y-m-d H:i:s T') }}">{{ $reading->recorded_at->format('M j, H:i') }}</time>
                                    <div class="text-muted monitoring-hint">{{ $reading->recorded_at->diffForHumans() }}</div>
                                </td>
                                <td class="text-nowrap">
                                    @if($reading->created_at)
                                        <time datetime="{{ $reading->created_at->toIso8601String() }}" title="{{ $reading->created_at->format('Y-m-d H:i:s T') }}">{{ $reading->created_at->format('M j, H:i') }}</time>
                                        @if($delay)
                                            <div class="monitoring-hint text-primary" title="Taken on the device {{ $delay }} before the server received it. The device was probably offline and uploaded it later.">
                                                <i class="bi bi-cloud-arrow-down me-1" aria-hidden="true"></i>{{ $delay }} late
                                            </div>
                                        @else
                                            <div class="text-muted monitoring-hint">on time</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($reading->hive)
                                        <a href="{{ route('admin.hives.show', $reading->hive) }}" class="text-decoration-none">{{ $reading->hive->display_name ?: $reading->hive->hive_code }}</a>
                                        <div class="text-muted monitoring-hint">{{ $reading->hive->apiary?->name ?? '—' }}</div>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">@include('admin.monitoring._device-link', ['reading' => $reading])</td>
                                @foreach(array_keys($metric->columns()) as $column)
                                    <td class="text-end tabular">{{ $number($reading->{$column}) }}</td>
                                @endforeach
                                <td>
                                    @if($reading->suspect)
                                        <span class="badge badge-warning"><i class="bi bi-flag-fill me-1" aria-hidden="true"></i>Flagged</span>
                                    @else
                                        <span class="badge badge-active"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>OK</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($readings->hasPages())
                <div class="card-footer bg-white">{{ $readings->links() }}</div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const { withAlpha, format, payload, fitRange, bandDatasets, pointRadius, mount } = window.MonitoringCharts;

            // ---- Trend chart ------------------------------------------------
            mount('sensorTrendChart', () => {
                const data = payload('script[data-sensor-chart]');

                if (!data) {
                    return null;
                }

                const { chart: trend, unit, precision } = data;
                const fmt = (value) => format(value, unit, precision);

                const datasets = trend.series.flatMap((series) => [
                    // Min/max envelope: the max line, then the min line filled up to it.
                    { label: series.label + ' (max)', data: series.max, role: 'envelope', seriesKey: series.column, borderWidth: 0, pointRadius: 0, fill: false, spanGaps: false, backgroundColor: withAlpha(series.color, 0.14) },
                    { label: series.label + ' (min)', data: series.min, role: 'envelope', seriesKey: series.column, borderWidth: 0, pointRadius: 0, fill: '-1', spanGaps: false, backgroundColor: withAlpha(series.color, 0.14) },
                    { label: series.label, data: series.avg, role: 'mean', seriesKey: series.column, borderColor: series.color, backgroundColor: series.color, borderWidth: 2, pointRadius: pointRadius(trend.labels.length), pointHoverRadius: 4, tension: 0.25, fill: false, spanGaps: false },
                ]);

                if (trend.band) {
                    datasets.push(...bandDatasets({ ...trend.band, label: 'Brood target' }, trend.labels.length, '#D4A017'));
                }

                // Fit the y-axis to the data (and the brood target), not to the
                // plausible range. The range lines are drawn but clipped away
                // unless readings come near them.
                const values = trend.series.flatMap((s) => s.min.concat(s.max))
                    .concat(trend.band ? [trend.band.min, trend.band.max] : []);

                for (const [label, bound] of [['Plausible min', trend.bounds.min], ['Plausible max', trend.bounds.max]]) {
                    datasets.push({ label, data: trend.labels.map(() => bound), role: 'bound', borderColor: '#b30000', borderDash: [6, 4], borderWidth: 1, pointRadius: 0, fill: false });
                }

                return {
                    type: 'line',
                    data: { labels: trend.labels, datasets },
                    options: {
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 8, maxRotation: 0, font: { size: 11 } } },
                            y: { ...fitRange(values, precision), grid: { color: 'rgba(27, 67, 50, 0.06)' }, ticks: { font: { size: 11 }, callback: (value) => fmt(value) } },
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 }, filter: (item, chartData) => !['envelope', 'band'].includes(chartData.datasets[item.datasetIndex].role) },
                                // Toggling a zone also toggles its envelope.
                                onClick: (event, item, legend) => {
                                    const target = legend.chart.data.datasets[item.datasetIndex];
                                    const visible = !legend.chart.isDatasetVisible(item.datasetIndex);

                                    legend.chart.data.datasets.forEach((dataset, i) => {
                                        if (i === item.datasetIndex || (target.seriesKey && dataset.seriesKey === target.seriesKey)) {
                                            legend.chart.setDatasetVisibility(i, visible);
                                        }
                                    });

                                    legend.chart.update();
                                },
                            },
                            tooltip: {
                                filter: (item) => item.dataset.role === 'mean' && item.raw !== null,
                                callbacks: {
                                    label: (context) => {
                                        const series = trend.series.find((s) => s.column === context.dataset.seriesKey);
                                        const i = context.dataIndex;
                                        return `${context.dataset.label}: ${fmt(context.raw)} (range ${fmt(series.min[i])} – ${fmt(series.max[i])})`;
                                    },
                                },
                            },
                        },
                    },
                };
            });

            // ---- Weight: daily change ---------------------------------------
            mount('weightChangeChart', () => {
                const change = payload('script[data-weight-change]');

                if (!change) {
                    return null;
                }

                const fmt = (value) => (value > 0 ? '+' : '') + format(value, 'kg', 2);

                return {
                    type: 'bar',
                    data: {
                        labels: change.labels,
                        datasets: [{
                            label: 'Weight change',
                            data: change.values,
                            backgroundColor: change.values.map((v) => v !== null && v < 0 ? withAlpha('#B30000', 0.75) : withAlpha('#2D6A4F', 0.8)),
                            borderRadius: 3,
                            maxBarThickness: 28,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 10, maxRotation: 0, font: { size: 11 } } },
                            y: { grid: { color: (ctx) => ctx.tick.value === 0 ? 'rgba(27, 67, 50, 0.35)' : 'rgba(27, 67, 50, 0.06)' }, ticks: { font: { size: 11 }, callback: (value) => fmt(value) } },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: { filter: (item) => item.raw !== null, callbacks: { label: (context) => fmt(context.raw) } },
                        },
                    },
                };
            });
        })();
    </script>
@endpush
