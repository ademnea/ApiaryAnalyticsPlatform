<?php

namespace App\Services\Anomaly;

use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\SensorAnomaly;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Anomaly incidents for the admin UI: filterable listing, detail history,
 * and the acknowledge/resolve lifecycle. Serves both categories — hive
 * conditions and device issues.
 */
class AnomalyIncidentService
{
    public const CATEGORIES = ['hive' => 'Hive conditions', 'device' => 'Device issues', 'all' => 'All'];
    public const STATUSES = ['unresolved' => 'Unresolved', 'open' => 'Open', 'acknowledged' => 'Acknowledged', 'resolved' => 'Resolved'];
    public const SEVERITIES = ['critical' => 'Critical', 'warning' => 'Warning', 'info' => 'Info'];

    /** Normalises raw query-string input into known filter values. */
    public function filtersFrom(array $input): array
    {
        return [
            'category' => array_key_exists($input['category'] ?? '', self::CATEGORIES) ? $input['category'] : 'hive',
            'status' => array_key_exists($input['status'] ?? '', self::STATUSES) ? $input['status'] : null,
            'severity' => array_key_exists($input['severity'] ?? '', self::SEVERITIES) ? $input['severity'] : null,
            'type' => is_string($input['type'] ?? null) && $input['type'] !== '' ? $input['type'] : null,
            'hive_id' => (int) ($input['hive_id'] ?? 0) ?: null,
            'device_id' => (int) ($input['device_id'] ?? 0) ?: null,
            'from' => $this->parseDate($input['from'] ?? null),
            'to' => $this->parseDate($input['to'] ?? null),
        ];
    }

    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->scoped($filters['category'])
            ->with(['device', 'hive'])
            ->when($filters['status'], fn ($q, $status) => $q->withStatus($status))
            ->when($filters['severity'], fn ($q, $severity) => $q->withSeverity($severity))
            ->when($filters['type'], fn ($q, $type) => $q->where('anomaly_type', $type))
            ->when($filters['hive_id'], fn ($q, $id) => $q->where('hive_id', $id))
            ->when($filters['device_id'], fn ($q, $id) => $q->where('device_id', $id))
            ->when($filters['from'], fn ($q, $from) => $q->where('detected_at', '>=', $from->copy()->startOfDay()))
            ->when($filters['to'], fn ($q, $to) => $q->where('detected_at', '<=', $to->copy()->endOfDay()))
            // Unresolved first, then most recently active.
            ->orderBy('resolved')
            ->orderByRaw('COALESCE(last_seen_at, detected_at) DESC')
            ->paginate($perPage);
    }

    /** Dropdown options limited to what exists in the given category. */
    public function filterOptions(string $category): array
    {
        return [
            'types' => $this->scoped($category)->distinct()->orderBy('anomaly_type')->pluck('anomaly_type'),
            'hives' => Hive::whereIn('id', $this->scoped($category)->whereNotNull('hive_id')->distinct()->select('hive_id'))->get(),
            'devices' => IotDevice::withTrashed()
                ->whereIn('id', $this->scoped($category)->distinct()->select('device_id'))
                ->orderBy('device_code')
                ->get(['id', 'device_code']),
        ];
    }

    public function loadDetail(SensorAnomaly $anomaly): SensorAnomaly
    {
        return $anomaly->load([
            'device' => fn ($q) => $q->withTrashed(),
            'hive.apiary',
            'acknowledgedBy',
            'resolvedBy',
            'alerts' => fn ($q) => $q->with('farmer')->orderByDesc('created_at'),
        ]);
    }

    /** Earlier incidents of the same kind on the same device — helps spot recurrence. */
    public function history(SensorAnomaly $anomaly, int $limit = 10): Collection
    {
        return SensorAnomaly::query()
            ->where('device_id', $anomaly->device_id)
            ->where('sensor_type', $anomaly->sensor_type)
            ->where('anomaly_type', $anomaly->anomaly_type)
            ->whereKeyNot($anomaly->id)
            ->orderByDesc('detected_at')
            ->limit($limit)
            ->get();
    }

    /** @return bool false when the incident was already resolved */
    public function acknowledge(SensorAnomaly $anomaly, User $by): bool
    {
        if ($anomaly->resolved) {
            return false;
        }

        $anomaly->acknowledge($by);

        return true;
    }

    /** @return bool false when the incident was already resolved */
    public function resolve(SensorAnomaly $anomaly, User $by, ?string $note): bool
    {
        if ($anomaly->resolved) {
            return false;
        }

        $anomaly->resolve($by, $note);

        return true;
    }

    private function scoped(string $category): Builder
    {
        return SensorAnomaly::query()
            ->when($category === 'hive', fn ($q) => $q->hiveConditions())
            ->when($category === 'device', fn ($q) => $q->deviceIssues());
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
