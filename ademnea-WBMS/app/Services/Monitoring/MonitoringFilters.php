<?php

namespace App\Services\Monitoring;

use App\Models\Hive;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The scope every Sensor Monitoring query runs in: which hives, which time
 * window, and whether to show flagged readings only.
 *
 * Built once per request, so the stats, the chart and the table on a page
 * always cover exactly the same window. It also decides how that window is
 * cut into chart buckets.
 */
final class MonitoringFilters
{
    /** Preset windows offered in the filter bar, in days. */
    public const PRESETS = ['24h' => 1, '7d' => 7, '30d' => 30, '90d' => 90];

    public const DEFAULT_RANGE = '7d';

    /** Longest custom window. Reading tables grow without limit, so every query stays bounded. */
    public const MAX_RANGE_DAYS = 90;

    /** Rows-per-page choices for the readings table. */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 25;

    /**
     * Table orderings: when the device took the reading, or when the server
     * received it. They differ whenever a device uploads a backlog.
     */
    public const SORTS = ['recorded' => 'recorded_at', 'received' => 'created_at'];

    public const DEFAULT_SORT = 'recorded';

    /** Windows up to a week are charted per hour (at most 169 points), longer ones per day. */
    private const HOURLY_UP_TO_HOURS = 168;

    /**
     * @param  ?int  $upto  Highest row id the table may show. Pagination links
     *                      carry it so pages 2+ stay put while new readings arrive.
     */
    private function __construct(
        public readonly ?int $apiaryId,
        public readonly ?int $hiveId,
        public readonly string $range,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly bool $flaggedOnly,
        public readonly int $perPage = self::DEFAULT_PER_PAGE,
        public readonly string $sort = self::DEFAULT_SORT,
        public readonly ?int $upto = null,
    ) {}

    /**
     * Validates the filter bar's query string. Invalid input redirects back
     * with errors, just as a form request would.
     */
    public static function fromRequest(Request $request): self
    {
        $validator = validator($request->all(), [
            'apiary_id' => ['nullable', 'integer', 'exists:apiaries,id'],
            'hive_id' => ['nullable', 'integer', 'exists:hives,id'],
            'range' => ['nullable', Rule::in([...array_keys(self::PRESETS), 'custom'])],
            'from' => ['nullable', 'date', 'before_or_equal:today'],
            // Compared with `from` only when one was sent. Otherwise Laravel
            // parses the literal word "from" as a date and rejects the request.
            'to' => array_values(array_filter([
                'nullable', 'date', 'before_or_equal:today',
                $request->filled('from') ? 'after_or_equal:from' : null,
            ])),
            'flagged' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'upto' => ['nullable', 'integer', 'min:1'],
        ], [
            'to.after_or_equal' => 'The end date must fall on or after the start date.',
        ], [
            'apiary_id' => 'apiary', 'hive_id' => 'hive', 'from' => 'start date', 'to' => 'end date',
        ]);

        $validator->after(function (Validator $validator) use ($request): void {
            if ($validator->errors()->isNotEmpty() || ! $request->filled(['from', 'to'])) {
                return;
            }

            $days = CarbonImmutable::parse($request->input('from'))
                ->diffInDays(CarbonImmutable::parse($request->input('to')), true);

            if ($days > self::MAX_RANGE_DAYS) {
                $validator->errors()->add('to', 'Choose a window of '.self::MAX_RANGE_DAYS.' days or fewer.');
            }
        });

        return self::fromArray($validator->validate());
    }

    /** @param  array<string, mixed>  $input  validated filter input */
    public static function fromArray(array $input): self
    {
        $hiveId = self::intOrNull($input['hive_id'] ?? null);

        // Typed dates win over the preset, which the form also submits as a
        // hidden field. "custom" with both dates cleared means the default.
        $hasDates = ! empty($input['from']) || ! empty($input['to']);
        $range = $hasDates ? 'custom' : (string) ($input['range'] ?? self::DEFAULT_RANGE);

        if (! $hasDates && ! array_key_exists($range, self::PRESETS)) {
            $range = self::DEFAULT_RANGE;
        }

        $now = CarbonImmutable::now();

        [$from, $to] = $range === 'custom'
            ? self::customWindow($input, $now)
            : [$now->subDays(self::PRESETS[$range]), $now];

        return new self(
            // A chosen hive is more specific than an apiary, so it replaces it.
            apiaryId: $hiveId === null ? self::intOrNull($input['apiary_id'] ?? null) : null,
            hiveId: $hiveId,
            range: $range,
            from: $from,
            to: $to,
            flaggedOnly: filter_var($input['flagged'] ?? false, FILTER_VALIDATE_BOOLEAN),
            perPage: in_array((int) ($input['per_page'] ?? 0), self::PER_PAGE_OPTIONS, true) ? (int) $input['per_page'] : self::DEFAULT_PER_PAGE,
            sort: array_key_exists((string) ($input['sort'] ?? ''), self::SORTS) ? (string) $input['sort'] : self::DEFAULT_SORT,
            upto: self::intOrNull($input['upto'] ?? null),
        );
    }

    /** The column the readings table is ordered by, newest first. */
    public function sortColumn(): string
    {
        return self::SORTS[$this->sort];
    }

    /** Narrows a reading, media or anomaly query to this scope. */
    public function applyTo(Builder $query, string $timeColumn = 'recorded_at'): Builder
    {
        return $query
            ->whereBetween($timeColumn, [$this->from, $this->to])
            ->when($this->hiveId, fn (Builder $q, int $id) => $q->where('hive_id', $id))
            ->when($this->apiaryId, fn (Builder $q, int $id) => $q->whereIn(
                'hive_id',
                Hive::query()->where('apiary_id', $id)->select('id')
            ));
    }

    public function isCustom(): bool
    {
        return $this->range === 'custom';
    }

    public function hasAnyScope(): bool
    {
        return $this->apiaryId !== null
            || $this->hiveId !== null
            || $this->flaggedOnly
            || $this->range !== self::DEFAULT_RANGE;
    }

    public function windowLabel(): string
    {
        return match ($this->range) {
            '24h' => 'Last 24 hours',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '90d' => 'Last 90 days',
            default => $this->from->format('M j, Y').' — '.$this->to->format('M j, Y'),
        };
    }

    /**
     * The active filters as query parameters, so pagination links, tabs and
     * the export button never drop them. Defaults are left out, and so is
     * `upto`: a tab, a new filter or an export always starts from live data.
     *
     * @return array<string, scalar>
     */
    public function queryString(): array
    {
        return array_filter([
            'apiary_id' => $this->apiaryId,
            'hive_id' => $this->hiveId,
            'range' => $this->range,
            'from' => $this->isCustom() ? $this->from->toDateString() : null,
            'to' => $this->isCustom() ? $this->to->toDateString() : null,
            'flagged' => $this->flaggedOnly ? 1 : null,
            'per_page' => $this->perPage !== self::DEFAULT_PER_PAGE ? $this->perPage : null,
            'sort' => $this->sort !== self::DEFAULT_SORT ? $this->sort : null,
        ], static fn ($value) => $value !== null);
    }

    // ---- Chart buckets ------------------------------------------------------

    public function isHourly(): bool
    {
        return $this->from->diffInHours($this->to, true) <= self::HOURLY_UP_TO_HOURS;
    }

    /**
     * MySQL expression truncating `recorded_at` to a bucket. DATE_FORMAT
     * returns the timestamp text exactly as Laravel stored it, so the keys
     * match bucketKeys() whatever the database session time zone is.
     */
    public function bucketSql(): string
    {
        return $this->isHourly()
            ? "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00')"
            : "DATE_FORMAT(recorded_at, '%Y-%m-%d')";
    }

    /**
     * Every bucket in the window, empty ones included, so an outage is drawn
     * as a gap rather than as a straight line across it.
     *
     * @return array<int, string>
     */
    public function bucketKeys(): array
    {
        return $this->keys($this->isHourly());
    }

    /**
     * Every calendar day in the window, for charts that are always daily
     * whatever the window length. Matches DATE_FORMAT(…, '%Y-%m-%d').
     *
     * @return array<int, string>
     */
    public function dayKeys(): array
    {
        return $this->keys(hourly: false);
    }

    public function bucketLabel(string $key): string
    {
        return CarbonImmutable::parse($key)->format($this->isHourly() ? 'M j, H:i' : 'M j');
    }

    /** @return array<int, string> */
    private function keys(bool $hourly): array
    {
        $cursor = $hourly ? $this->from->startOfHour() : $this->from->startOfDay();
        $keys = [];

        while ($cursor->lessThanOrEqualTo($this->to)) {
            $keys[] = $cursor->format($hourly ? 'Y-m-d H:00:00' : 'Y-m-d');
            $cursor = $hourly ? $cursor->addHour() : $cursor->addDay();
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function customWindow(array $input, CarbonImmutable $now): array
    {
        $to = empty($input['to']) ? $now : CarbonImmutable::parse($input['to'])->endOfDay();
        $from = empty($input['from'])
            ? $to->subDays(self::PRESETS[self::DEFAULT_RANGE])
            : CarbonImmutable::parse($input['from'])->startOfDay();

        // Validation already rejects both cases; these guards keep the object
        // safe when it is built directly, as in tests or jobs.
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to, true) > self::MAX_RANGE_DAYS) {
            $from = $to->subDays(self::MAX_RANGE_DAYS);
        }

        return [$from, $to];
    }

    private static function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
