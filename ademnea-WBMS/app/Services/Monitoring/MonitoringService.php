<?php

namespace App\Services\Monitoring;

use App\Enums\MediaKind;
use App\Enums\SensorMetric;
use App\Models\AlertThreshold;
use App\Models\Apiary;
use App\Models\Hive;
use App\Models\SensorAnomaly;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Generator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Read model for the Sensor Monitoring pages.
 *
 * Every count, average and chart bucket is computed in SQL. The reading
 * tables are append-only and grow without limit, so nothing here loads rows
 * into PHP just to aggregate them.
 */
class MonitoringService
{
    /** Relations every reading or capture row is displayed with. */
    private const DISPLAY_RELATIONS = [
        'hive:id,hive_code,display_name,apiary_id',
        'hive.apiary:id,name',
        'device:id,device_code',
    ];

    /** A reading received this many minutes after it was recorded counts as delivered late. */
    public const LATE_AFTER_MINUTES = 15;

    /** Most rows in the "Latest by device" panel. */
    public const LATEST_DEVICE_LIMIT = 50;

    // ---- Numeric sensors ----------------------------------------------------

    /**
     * Headline counts plus latest, min, avg and max per column, in two queries.
     *
     * `last_reading_at` is when the newest reading was taken on the device;
     * `last_received_at` is when the server last got any reading. They drift
     * apart when a device uploads a backlog. `latest_id` pins the readings
     * table to this moment (see readings()).
     *
     * @return array{readings: int, flagged: int, hives_reporting: int, devices_reporting: int, last_reading_at: ?CarbonImmutable, last_received_at: ?CarbonImmutable, latest_id: ?int, columns: array<string, array{label: string, color: string, latest: ?float, min: ?float, max: ?float, avg: ?float}>}
     */
    public function summary(SensorMetric $metric, MonitoringFilters $filters): array
    {
        $selects = [
            'COUNT(*) as readings_count',
            'SUM(suspect) as flagged_count',
            'COUNT(DISTINCT hive_id) as hives_reporting',
            'COUNT(DISTINCT device_id) as devices_reporting',
            'MAX(recorded_at) as last_reading_at',
            'MAX(created_at) as last_received_at',
            'MAX(id) as latest_id',
        ];

        foreach (array_keys($metric->columns()) as $column) {
            array_push($selects, "MIN({$column}) as min_{$column}", "MAX({$column}) as max_{$column}", "AVG({$column}) as avg_{$column}");
        }

        $totals = $this->scoped($metric, $filters)->selectRaw(implode(', ', $selects))->first();

        // A device sends every zone in one payload, so the newest row holds
        // the current value of each zone.
        $latest = $this->scoped($metric, $filters)->latest('recorded_at')->first();

        $columns = [];

        foreach ($metric->columns() as $column => $label) {
            $columns[$column] = [
                'label' => $label,
                'color' => $metric->colorFor($column),
                'latest' => $this->float($latest?->{$column}),
                'min' => $this->float($totals->{"min_{$column}"}),
                'max' => $this->float($totals->{"max_{$column}"}),
                'avg' => $this->float($totals->{"avg_{$column}"}),
            ];
        }

        return [
            'readings' => (int) $totals->readings_count,
            'flagged' => (int) $totals->flagged_count,
            'hives_reporting' => (int) $totals->hives_reporting,
            'devices_reporting' => (int) $totals->devices_reporting,
            'last_reading_at' => $this->moment($totals->last_reading_at),
            'last_received_at' => $this->moment($totals->last_received_at),
            'latest_id' => $totals->latest_id === null ? null : (int) $totals->latest_id,
            'columns' => $columns,
        ];
    }

    /**
     * Chart data: for each bucket, the mean line and min/max envelope of every
     * column. Empty buckets are kept as nulls so outages show as gaps.
     *
     * @return array{labels: array<int, string>, series: array<int, array{column: string, label: string, color: string, avg: array<int, ?float>, min: array<int, ?float>, max: array<int, ?float>}>, bounds: array{min: float, max: float}, band: array{min: float, max: float}|null, has_data: bool}
     */
    public function series(SensorMetric $metric, MonitoringFilters $filters): array
    {
        $bucket = $filters->bucketSql();
        $selects = ["{$bucket} as bucket"];

        foreach (array_keys($metric->columns()) as $column) {
            array_push($selects, "AVG({$column}) as avg_{$column}", "MIN({$column}) as min_{$column}", "MAX({$column}) as max_{$column}");
        }

        $rows = $this->scoped($metric, $filters)
            ->selectRaw(implode(', ', $selects))
            ->groupByRaw($bucket)
            ->get()
            ->keyBy('bucket');

        $keys = $filters->bucketKeys();
        $pluck = fn (string $field) => array_map(fn (string $key) => $this->float($rows->get($key)?->{$field}), $keys);

        [$min, $max] = $metric->bounds($filters->hiveId);

        return [
            'labels' => array_map($filters->bucketLabel(...), $keys),
            'series' => collect($metric->columns())->map(fn (string $label, string $column) => [
                'column' => $column,
                'label' => $label,
                'color' => $metric->colorFor($column),
                'avg' => $pluck("avg_{$column}"),
                'min' => $pluck("min_{$column}"),
                'max' => $pluck("max_{$column}"),
            ])->values()->all(),
            'bounds' => ['min' => $min, 'max' => $max],
            'band' => $metric->idealBand($filters->hiveId),
            'has_data' => $rows->isNotEmpty(),
        ];
    }

    /**
     * The readings table, newest first by the chosen sort.
     *
     * New readings would push rows from one page onto the next while someone
     * pages through, so every pagination link carries `upto`: the highest id
     * in scope when the page was rendered. Ids only grow on these append-only
     * tables, so a page with `upto` never shifts, even when a backlog brings
     * in readings with old recorded_at times.
     *
     * @param  ?int  $latestId  highest id in scope now, from summary()
     */
    public function readings(SensorMetric $metric, MonitoringFilters $filters, ?int $latestId = null): LengthAwarePaginator
    {
        return $this->scoped($metric, $filters)
            ->when($filters->upto, fn (Builder $q, int $id) => $q->where('id', '<=', $id))
            ->with(self::DISPLAY_RELATIONS)
            ->orderByDesc($filters->sortColumn())
            ->orderByDesc('id')
            ->paginate($filters->perPage)
            ->withQueryString()
            ->appends(array_filter(['upto' => $filters->upto ?? $latestId]))
            ->fragment('readings');
    }

    /** Rows in scope that arrived after the table was pinned with `upto`. */
    public function newSince(SensorMetric|MediaKind $stream, MonitoringFilters $filters): int
    {
        if ($filters->upto === null) {
            return 0;
        }

        $query = $stream instanceof SensorMetric
            ? $this->scoped($stream, $filters)
            : $filters->applyTo($stream->modelClass()::query());

        return $query->where('id', '>', $filters->upto)->count();
    }

    /**
     * Each device's newest reading in scope, with when the server last heard
     * from it. The (device_id, recorded_at) unique constraint on every sensor
     * table makes the join back to the row exact.
     *
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function latestPerDevice(SensorMetric $metric, MonitoringFilters $filters): Collection
    {
        $model = $metric->modelClass();
        $table = (new $model)->getTable();

        $latest = $this->scoped($metric, $filters)
            ->toBase()
            ->selectRaw('device_id, MAX(recorded_at) as last_recorded_at, MAX(created_at) as last_received_at, COUNT(*) as readings_count')
            ->groupBy('device_id');

        return $model::query()
            ->joinSub($latest, 'latest', fn (JoinClause $join) => $join
                ->on("{$table}.device_id", '=', 'latest.device_id')
                ->on("{$table}.recorded_at", '=', 'latest.last_recorded_at'))
            ->select(["{$table}.*", 'latest.last_received_at', 'latest.readings_count'])
            ->withCasts(['last_received_at' => 'immutable_datetime', 'readings_count' => 'integer'])
            ->with(self::DISPLAY_RELATIONS)
            ->orderByDesc("{$table}.recorded_at")
            ->limit(self::LATEST_DEVICE_LIMIT)
            ->get();
    }

    /**
     * CSV rows, oldest first, read 500 at a time so a large export never sits
     * in memory. Offset chunking rather than lazyById, which would break the
     * recorded_at ordering and could skip or repeat rows.
     *
     * @return Generator<int, array<int, string>>
     */
    public function exportRows(SensorMetric $metric, MonitoringFilters $filters): Generator
    {
        $columns = $metric->columns();

        yield [
            'Recorded At (UTC)', 'Received At (UTC)', 'Hive', 'Apiary', 'Device',
            ...array_map(fn (string $label) => "{$label} ({$metric->unit()})", array_values($columns)),
            'Flagged',
        ];

        $readings = $this->scoped($metric, $filters)
            ->with(self::DISPLAY_RELATIONS)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->lazy(500);

        foreach ($readings as $reading) {
            yield [
                $reading->recorded_at->toDateTimeString(),
                $reading->created_at?->toDateTimeString() ?? '',
                $reading->hive?->display_name ?: ($reading->hive?->hive_code ?? ''),
                $reading->hive?->apiary?->name ?? '',
                $reading->device?->device_code ?? '',
                ...array_map(fn (string $column) => (string) $reading->{$column}, array_keys($columns)),
                $reading->suspect ? 'yes' : 'no',
            ];
        }
    }

    /**
     * Average day-over-day change in hive weight, in kg. Each hive is compared
     * with its own previous day before averaging, so a hive that starts or
     * stops reporting does not look like the fleet gained or lost weight.
     * Always daily: hourly weight moves with foragers leaving and returning.
     *
     * @return array{labels: array<int, string>, values: array<int, ?float>, hives: int, has_data: bool}
     */
    public function weightDailyChange(MonitoringFilters $filters): array
    {
        $day = "DATE_FORMAT(recorded_at, '%Y-%m-%d')";

        $byHive = $this->scoped(SensorMetric::Weight, $filters)
            ->toBase()
            ->selectRaw("hive_id, {$day} as day, AVG(weight_kg) as avg_weight")
            ->groupBy('hive_id')
            ->groupByRaw($day)
            ->get()
            ->groupBy('hive_id')
            ->map(fn (Collection $rows) => $rows->pluck('avg_weight', 'day'));

        $keys = $filters->dayKeys();
        $values = [];

        foreach ($keys as $i => $key) {
            $previous = $keys[$i - 1] ?? null;

            $changes = $previous === null ? collect() : $byHive
                ->filter(fn (Collection $days) => isset($days[$key], $days[$previous]))
                ->map(fn (Collection $days) => (float) $days[$key] - (float) $days[$previous]);

            $values[] = $changes->isEmpty() ? null : round($changes->avg(), 3);
        }

        return [
            'labels' => array_map(fn (string $key) => CarbonImmutable::parse($key)->format('M j'), $keys),
            'values' => $values,
            'hives' => $byHive->count(),
            'has_data' => collect($values)->contains(fn (?float $value) => $value !== null),
        ];
    }

    /** Unresolved anomalies raised in the window, optionally for one metric only. */
    public function openAnomalies(MonitoringFilters $filters, ?SensorMetric $metric = null): int
    {
        return $filters->applyTo(SensorAnomaly::query(), 'detected_at')
            ->where('resolved', false)
            ->when($metric, fn (Builder $q, SensorMetric $m) => $q->where('sensor_type', $m->value))
            ->count();
    }

    // ---- Insights -----------------------------------------------------------

    /**
     * Paired charts for one hive, each answering a beekeeping question that a
     * single stream cannot. Built from series(), so every line shares the
     * same buckets and lines up point for point across the four tables.
     *
     * @return array{labels: array<int, string>, charts: array<string, array<string, mixed>>}
     */
    public function insights(MonitoringFilters $filters): array
    {
        $series = collect(SensorMetric::cases())
            ->mapWithKeys(fn (SensorMetric $metric) => [$metric->value => $this->series($metric, $filters)]);

        $line = function (SensorMetric $metric, string $column, string $label, string $color, string $axis = 'y', bool $dashed = false) use ($series): array {
            $data = collect($series[$metric->value]['series'])->firstWhere('column', $column)['avg'];

            return compact('label', 'color', 'data', 'axis', 'dashed') + ['unit' => $metric->unit(), 'precision' => $metric->precision()];
        };

        $axis = fn (SensorMetric $metric, string $title) => ['title' => "{$title} ({$metric->unit()})", 'unit' => $metric->unit(), 'precision' => $metric->precision()];

        $broodTemp = fn (string $axis = 'y', bool $dashed = false) => $line(SensorMetric::Temperature, 'brood_section', 'Brood temperature', SensorMetric::Temperature->streamColor(), $axis, $dashed);
        $exteriorTemp = fn (string $axis = 'y') => $line(SensorMetric::Temperature, 'exterior', 'Exterior temperature', '#0057b8', $axis, dashed: true);

        $charts = [
            'thermoregulation' => [
                'title' => 'Thermoregulation',
                'icon' => 'bi-thermometer-half',
                'question' => 'Is the colony keeping its brood warm whatever the weather?',
                'guide' => 'A strong colony holds the brood line flat inside the shaded band while the exterior line swings with day and night. If the brood line starts following the exterior line, the colony is weak, queenless or has absconded.',
                'datasets' => [$broodTemp(), $exteriorTemp()],
                'axes' => ['y' => $axis(SensorMetric::Temperature, 'Temperature')],
                'bands' => array_filter([$this->band(SensorMetric::Temperature, $filters, 'y', 'Brood target')]),
            ],
            'climate' => [
                'title' => 'Brood temperature vs humidity',
                'icon' => 'bi-droplet-half',
                'question' => 'Is the brood nest both warm and moist enough?',
                'guide' => 'The shaded band is the humidity target. Humidity that is too low stops eggs hatching; humidity that stays high while temperature rises points to poor ventilation.',
                'datasets' => [
                    $broodTemp(),
                    $line(SensorMetric::Humidity, 'brood_section', 'Brood humidity', SensorMetric::Humidity->streamColor(), 'y1'),
                ],
                'axes' => ['y' => $axis(SensorMetric::Temperature, 'Temperature'), 'y1' => $axis(SensorMetric::Humidity, 'Humidity')],
                'bands' => array_filter([$this->band(SensorMetric::Humidity, $filters, 'y1', 'Humidity target')]),
            ],
            'ventilation' => [
                'title' => 'CO₂ vs brood temperature',
                'icon' => 'bi-wind',
                'question' => 'Is the hive ventilated well enough for its population?',
                'guide' => 'CO₂ rises at night and falls when bees fan. CO₂ and brood temperature climbing together, day after day, suggest overcrowding or blocked ventilation, which often comes before swarming.',
                'datasets' => [
                    $line(SensorMetric::Co2, 'co2_level', 'CO₂', SensorMetric::Co2->streamColor()),
                    $broodTemp('y1', dashed: true),
                ],
                'axes' => ['y' => $axis(SensorMetric::Co2, 'CO₂'), 'y1' => $axis(SensorMetric::Temperature, 'Temperature')],
                'bands' => [],
            ],
            'foraging' => [
                'title' => 'Hive weight vs exterior temperature',
                'icon' => 'bi-flower1',
                'question' => 'Are the bees bringing in nectar, or living off their stores?',
                'guide' => 'Weight that climbs on warm days means foraging and a nectar flow. Weight that falls through cold or wet spells means the colony is eating its stores and may need feeding. A sudden drop of a few kilograms usually means a swarm or a harvest.',
                'datasets' => [
                    $line(SensorMetric::Weight, 'weight_kg', 'Hive weight', SensorMetric::Weight->streamColor()),
                    $exteriorTemp('y1'),
                ],
                'axes' => ['y' => $axis(SensorMetric::Weight, 'Weight'), 'y1' => $axis(SensorMetric::Temperature, 'Temperature')],
                'bands' => [],
            ],
        ];

        $charts = array_map(fn (array $chart) => $chart + [
            'has_data' => collect($chart['datasets'])
                ->contains(fn (array $dataset) => collect($dataset['data'])->contains(fn (?float $value) => $value !== null)),
        ], $charts);

        return ['labels' => $series[SensorMetric::Temperature->value]['labels'], 'charts' => $charts];
    }

    // ---- Media --------------------------------------------------------------

    /** @return array{files: int, hives_reporting: int, devices_reporting: int, total_bytes: int, total_seconds: int, last_capture_at: ?CarbonImmutable, latest_id: ?int} */
    public function mediaSummary(MediaKind $kind, MonitoringFilters $filters): array
    {
        $duration = $kind->hasDuration() ? 'SUM(duration_seconds)' : '0';

        $row = $filters->applyTo($kind->modelClass()::query())
            ->selectRaw('COUNT(*) as files_count, COUNT(DISTINCT hive_id) as hives_reporting, '
                .'COUNT(DISTINCT device_id) as devices_reporting, SUM(file_size_bytes) as total_bytes, '
                ."{$duration} as total_seconds, MAX(recorded_at) as last_capture_at, MAX(id) as latest_id")
            ->first();

        return [
            'files' => (int) $row->files_count,
            'hives_reporting' => (int) $row->hives_reporting,
            'devices_reporting' => (int) $row->devices_reporting,
            'total_bytes' => (int) $row->total_bytes,
            'total_seconds' => (int) $row->total_seconds,
            'last_capture_at' => $this->moment($row->last_capture_at),
            'latest_id' => $row->latest_id === null ? null : (int) $row->latest_id,
        ];
    }

    /** Pinned with `upto` exactly like readings(). */
    public function mediaGallery(MediaKind $kind, MonitoringFilters $filters, ?int $latestId = null): LengthAwarePaginator
    {
        return $filters->applyTo($kind->modelClass()::query())
            ->when($filters->upto, fn (Builder $q, int $id) => $q->where('id', '<=', $id))
            ->with(self::DISPLAY_RELATIONS)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString()
            ->appends(array_filter(['upto' => $filters->upto ?? $latestId]))
            ->fragment('gallery');
    }

    // ---- Overview -----------------------------------------------------------

    /**
     * Per-stream headline numbers plus a sparkline of the primary column,
     * bucketed like the stream's own chart.
     *
     * @return array<string, array{metric: SensorMetric, readings: int, hives_reporting: int, average: ?float, last_reading_at: ?CarbonImmutable, spark: array<int, ?float>}>
     */
    public function sensorSnapshot(MonitoringFilters $filters): array
    {
        $bucket = $filters->bucketSql();
        $keys = $filters->bucketKeys();

        return collect(SensorMetric::cases())->mapWithKeys(function (SensorMetric $metric) use ($filters, $bucket, $keys) {
            $row = $this->scoped($metric, $filters)
                ->selectRaw('COUNT(*) as readings_count, COUNT(DISTINCT hive_id) as hives_reporting, '
                    ."AVG({$metric->primaryColumn()}) as average_value, MAX(recorded_at) as last_reading_at")
                ->first();

            $spark = $this->scoped($metric, $filters)
                ->toBase()
                ->selectRaw("{$bucket} as bucket, AVG({$metric->primaryColumn()}) as value")
                ->groupByRaw($bucket)
                ->pluck('value', 'bucket');

            return [$metric->value => [
                'metric' => $metric,
                'readings' => (int) $row->readings_count,
                'hives_reporting' => (int) $row->hives_reporting,
                'average' => $this->float($row->average_value),
                'last_reading_at' => $this->moment($row->last_reading_at),
                'spark' => array_map(fn (string $key) => $this->float($spark[$key] ?? null), $keys),
            ]];
        })->all();
    }

    /**
     * Readings received per bucket, one stacked series per stream. A dip in
     * one colour is an outage of that sensor; a dip in all is a device or
     * network outage.
     *
     * @return array{labels: array<int, string>, series: array<int, array{label: string, color: string, counts: array<int, int>}>, has_data: bool}
     */
    public function volumeSeries(MonitoringFilters $filters): array
    {
        $bucket = $filters->bucketSql();
        $keys = $filters->bucketKeys();

        $series = collect(SensorMetric::cases())->map(function (SensorMetric $metric) use ($filters, $bucket, $keys) {
            $counts = $this->scoped($metric, $filters)
                ->toBase()
                ->selectRaw("{$bucket} as bucket, COUNT(*) as readings_count")
                ->groupByRaw($bucket)
                ->pluck('readings_count', 'bucket');

            return [
                'label' => $metric->label(),
                'color' => $metric->streamColor(),
                'counts' => array_map(fn (string $key) => (int) ($counts[$key] ?? 0), $keys),
            ];
        })->all();

        return [
            'labels' => array_map($filters->bucketLabel(...), $keys),
            'series' => $series,
            'has_data' => collect($series)->contains(fn (array $s) => array_sum($s['counts']) > 0),
        ];
    }

    /** @return array<string, array{kind: MediaKind, files: int, last_capture_at: ?CarbonImmutable}> */
    public function mediaSnapshot(MonitoringFilters $filters): array
    {
        return collect(MediaKind::cases())->mapWithKeys(function (MediaKind $kind) use ($filters) {
            $row = $filters->applyTo($kind->modelClass()::query())
                ->selectRaw('COUNT(*) as files_count, MAX(recorded_at) as last_capture_at')
                ->first();

            return [$kind->value => [
                'kind' => $kind,
                'files' => (int) $row->files_count,
                'last_capture_at' => $this->moment($row->last_capture_at),
            ]];
        })->all();
    }

    public function hives(MonitoringFilters $filters): LengthAwarePaginator
    {
        return Hive::query()
            ->select(['id', 'hive_code', 'display_name', 'apiary_id'])
            ->with('apiary:id,name')
            ->when($filters->hiveId, fn (Builder $q, int $id) => $q->whereKey($id))
            ->when($filters->apiaryId, fn (Builder $q, int $id) => $q->where('apiary_id', $id))
            ->orderBy('display_name')
            ->paginate(15)
            ->withQueryString()
            ->fragment('coverage');
    }

    /**
     * When each hive last reported each sensor stream. Limited to the hive ids
     * on the current page, so its cost follows the page size, not the fleet.
     *
     * @param  array<int, int>  $hiveIds
     * @return Collection<string, Collection<int, CarbonImmutable>> metric value => [hive id => last reading]
     */
    public function coverage(array $hiveIds, MonitoringFilters $filters): Collection
    {
        return collect(SensorMetric::cases())->mapWithKeys(fn (SensorMetric $metric) => [
            $metric->value => $hiveIds === [] ? collect() : $filters->applyTo($metric->modelClass()::query())
                ->whereIn('hive_id', $hiveIds)
                ->selectRaw('hive_id, MAX(recorded_at) as last_reading_at')
                ->groupBy('hive_id')
                ->pluck('last_reading_at', 'hive_id')
                ->map(fn (string $at) => CarbonImmutable::parse($at)),
        ]);
    }

    // ---- Filter bar and freshness -------------------------------------------

    /** @return Collection<int, Apiary> */
    public function apiaries(): Collection
    {
        return Apiary::query()->select(['id', 'name'])->orderBy('name')->get();
    }

    /** @return Collection<string, Collection<int, Hive>> hives grouped by apiary name */
    public function hiveGroups(?int $apiaryId): Collection
    {
        return Hive::query()
            ->select(['id', 'hive_code', 'display_name', 'apiary_id'])
            ->with('apiary:id,name')
            ->when($apiaryId, fn (Builder $q, int $id) => $q->where('apiary_id', $id))
            ->orderBy('display_name')
            ->get()
            ->groupBy(fn (Hive $hive) => $hive->apiary?->name ?? 'Unassigned')
            ->sortKeys();
    }

    /** Minutes of silence before a stream is stale; the rules engine raises device_offline on the same value. */
    public function staleAfterMinutes(): int
    {
        return (int) AlertThreshold::get('device_offline_silence_minutes', 120);
    }

    public static function isStale(?CarbonInterface $lastAt, int $staleAfterMinutes): bool
    {
        return $lastAt === null || $lastAt->lt(CarbonImmutable::now()->subMinutes($staleAfterMinutes));
    }

    /**
     * The newest reading is old but the server heard from the device
     * recently: it is uploading a backlog, not offline.
     */
    public static function isBacklog(?CarbonInterface $lastRecordedAt, ?CarbonInterface $lastReceivedAt, int $staleAfterMinutes): bool
    {
        return $lastRecordedAt !== null
            && self::isStale($lastRecordedAt, $staleAfterMinutes)
            && ! self::isStale($lastReceivedAt, $staleAfterMinutes);
    }

    /** How long after recording a reading reached the server, when that is long enough to mention ("4 days"). */
    public static function deliveryDelay(CarbonInterface $recordedAt, ?CarbonInterface $receivedAt): ?string
    {
        if ($receivedAt === null || $recordedAt->diffInMinutes($receivedAt) <= self::LATE_AFTER_MINUTES) {
            return null;
        }

        return $receivedAt->diffForHumans($recordedAt, ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1]);
    }

    /** Readings in scope: the window, the hive or apiary, and the flagged-only switch. */
    private function scoped(SensorMetric $metric, MonitoringFilters $filters): Builder
    {
        return $filters->applyTo($metric->modelClass()::query())
            ->when($filters->flaggedOnly, fn (Builder $q) => $q->where('suspect', true));
    }

    /** @return array{axis: string, label: string, min: float, max: float}|null */
    private function band(SensorMetric $metric, MonitoringFilters $filters, string $axis, string $label): ?array
    {
        $band = $metric->idealBand($filters->hiveId);

        return $band === null ? null : ['axis' => $axis, 'label' => $label] + $band;
    }

    private function float(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function moment(mixed $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value);
    }
}
