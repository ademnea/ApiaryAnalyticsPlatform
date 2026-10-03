<?php

namespace App\Services\Anomaly;

use App\Models\Alert;
use App\Models\Hive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * SRS REQ-F-IOT-17 (alert routing), admin side: every alert dispatched to a
 * farmer — from AnomalyAlertDispatchService and the feed-threshold job —
 * with read state and the anomaly incident that raised it.
 */
class SystemAlertService
{
    /** Mirrors the alerts.type enum. */
    public const TYPES = ['feed_required', 'malfunction', 'critical_event', 'low_battery', 'weak_signal', 'data_anomaly'];

    public function filtersFrom(array $input): array
    {
        return [
            'type' => in_array($input['type'] ?? null, self::TYPES, true) ? $input['type'] : null,
            'read' => in_array($input['read'] ?? null, ['read', 'unread'], true) ? $input['read'] : null,
            'source' => in_array($input['source'] ?? null, ['anomaly', 'other'], true) ? $input['source'] : null,
            'hive_id' => (int) ($input['hive_id'] ?? 0) ?: null,
        ];
    }

    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return Alert::query()
            ->with(['farmer', 'hive', 'sourceAnomaly'])
            ->when($filters['type'], fn ($q, $type) => $q->where('type', $type))
            ->when($filters['read'] === 'read', fn ($q) => $q->where('is_read', true))
            ->when($filters['read'] === 'unread', fn ($q) => $q->where('is_read', false))
            ->when($filters['source'] === 'anomaly', fn ($q) => $q->whereNotNull('source_anomaly_id'))
            ->when($filters['source'] === 'other', fn ($q) => $q->whereNull('source_anomaly_id'))
            ->when($filters['hive_id'], fn ($q, $id) => $q->where('hive_id', $id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function kpis(): array
    {
        return [
            'last_24h' => Alert::where('created_at', '>=', now()->subDay())->count(),
            'unread' => Alert::where('is_read', false)->count(),
            'from_anomalies' => Alert::whereNotNull('source_anomaly_id')->where('created_at', '>=', now()->subDays(7))->count(),
            'total' => Alert::count(),
        ];
    }

    public function hiveOptions()
    {
        return Hive::whereIn('id', Alert::whereNotNull('hive_id')->distinct()->select('hive_id'))->get();
    }
}
