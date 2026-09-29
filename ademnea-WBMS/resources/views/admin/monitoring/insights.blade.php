@extends('admin.monitoring.layout')

{{--
    Paired charts for one hive. Averages across hives would blur exactly the
    differences these charts exist to show, so a hive must be chosen first.
--}}

@php
    use Illuminate\Support\Arr;
@endphp

@section('monitoring')
    @include('admin.monitoring._chart-helpers')

    @if($insights === null)
        <div class="card">
            <div class="card-body text-center py-5 px-3">
                <i class="bi bi-activity d-block mb-2 monitoring-empty-icon" aria-hidden="true"></i>
                <h2 class="h6 fw-semibold mb-1">Choose a hive to see its insights</h2>
                <p class="text-muted monitoring-hint mb-3 mx-auto" style="max-width: 36rem">
                    These charts pair two streams from the same hive: brood against exterior temperature, temperature against humidity,
                    CO₂ against temperature, and weight against the weather. Fleet averages would hide the signals they show.
                </p>
                @if($hiveGroups->isEmpty())
                    <p class="monitoring-hint mb-0">No hives are registered{{ $filters->apiaryId ? ' in this apiary' : '' }} yet.</p>
                @else
                    <form method="GET" action="{{ route('admin.monitoring.insights') }}" class="d-flex flex-wrap justify-content-center gap-2">
                        @foreach(Arr::except($filters->queryString(), ['apiary_id', 'hive_id']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <label for="insights-hive" class="visually-hidden">Hive</label>
                        <select id="insights-hive" name="hive_id" class="form-select form-select-sm w-auto" required>
                            <option value="">Select a hive…</option>
                            @foreach($hiveGroups as $apiaryName => $groupHives)
                                <optgroup label="{{ $apiaryName }}">
                                    @foreach($groupHives as $hive)
                                        <option value="{{ $hive->id }}">{{ $hive->display_name ?: $hive->hive_code }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Show insights</button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($insights['charts'] as $key => $chart)
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header"><i class="bi {{ $chart['icon'] }} me-1" aria-hidden="true"></i>{{ $chart['title'] }}</div>
                        <div class="card-body d-flex flex-column">
                            <p class="insight-question mb-2">{{ $chart['question'] }}</p>
                            @if($chart['has_data'])
                                <div class="chart-wrap chart-wrap--short">
                                    <canvas id="insight-{{ $key }}" role="img"
                                            aria-label="{{ $chart['title'] }} for {{ Str::lower($filters->windowLabel()) }}. {{ $chart['guide'] }}"></canvas>
                                </div>
                            @else
                                <div class="text-center text-muted py-5 flex-grow-1" role="status">
                                    <p class="fw-medium text-body mb-1">No readings for this pair yet</p>
                                    <p class="mb-0 monitoring-hint">This hive has not reported these streams in {{ Str::lower($filters->windowLabel()) }}.</p>
                                </div>
                            @endif
                            <p class="text-muted monitoring-hint mt-2 mb-0">
                                <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>{{ $chart['guide'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-muted monitoring-hint mt-3 mb-0">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            Solid lines use the left axis and dashed lines the right axis. Values are averaged into 1-{{ $filters->isHourly() ? 'hour' : 'day' }} buckets; gaps mean no readings arrived.
        </p>
        <script type="application/json" data-insights>@json($insights)</script>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const { withAlpha, format, payload, fitRange, bandDatasets, pointRadius, mount } = window.MonitoringCharts;

            for (const key of ['thermoregulation', 'climate', 'ventilation', 'foraging']) {
                mount('insight-' + key, () => {
                    const insights = payload('script[data-insights]');
                    const chart = insights?.charts[key];

                    if (!chart || !chart.has_data) {
                        return null;
                    }

                    const length = insights.labels.length;

                    const datasets = chart.datasets.map((d) => ({
                        label: d.label, data: d.data, yAxisID: d.axis, role: 'line', unit: d.unit, precision: d.precision,
                        borderColor: d.color, backgroundColor: d.color, borderWidth: 2, borderDash: d.dashed ? [6, 4] : [],
                        pointRadius: pointRadius(length), pointHoverRadius: 4, tension: 0.25, fill: false, spanGaps: false,
                    }));

                    for (const band of chart.bands) {
                        const owner = chart.datasets.find((d) => d.axis === band.axis);
                        datasets.push(...bandDatasets(band, length, owner?.color ?? '#D4A017', band.axis));
                    }

                    const scales = { x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 6, maxRotation: 0, font: { size: 11 } } } };

                    for (const [axisId, axis] of Object.entries(chart.axes)) {
                        const values = chart.datasets.filter((d) => d.axis === axisId).flatMap((d) => d.data)
                            .concat(chart.bands.filter((b) => b.axis === axisId).flatMap((b) => [b.min, b.max]));

                        scales[axisId] = {
                            ...fitRange(values, axis.precision),
                            position: axisId === 'y' ? 'left' : 'right',
                            title: { display: true, text: axis.title, font: { size: 11 } },
                            grid: axisId === 'y' ? { color: 'rgba(27, 67, 50, 0.06)' } : { drawOnChartArea: false },
                            ticks: { font: { size: 11 }, callback: (value) => format(value, '', axis.precision) },
                        };
                    }

                    return {
                        type: 'line',
                        data: { labels: insights.labels, datasets },
                        options: {
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            scales,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 }, filter: (item, data) => data.datasets[item.datasetIndex].role !== 'band' },
                                },
                                tooltip: {
                                    filter: (item) => item.dataset.role === 'line' && item.raw !== null,
                                    callbacks: { label: (context) => `${context.dataset.label}: ${format(context.raw, context.dataset.unit, context.dataset.precision)}` },
                                },
                            },
                        },
                    };
                });
            }
        })();
    </script>
@endpush
