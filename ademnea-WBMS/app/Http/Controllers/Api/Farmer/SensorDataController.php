<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\Sensor\SensorDataRequest;
use App\Models\Farmer;
use App\Services\Farmer\FarmerHiveAccessService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * REQ-F-FAPI-14 to 18: Sensor data read-only endpoints.
 *
 * Routes:
 *   GET /api/v1/farmer/hives/{hive_id}/temperature
 *   GET /api/v1/farmer/hives/{hive_id}/humidity
 *   GET /api/v1/farmer/hives/{hive_id}/carbondioxide
 *   GET /api/v1/farmer/hives/{hive_id}/weight
 *   GET /api/v1/farmer/hives/{hive_id}/latest    ← aggregates all 4 in one call
 *
 * All queries go through Dev C's tables:
 *   hive_temperatures, hive_humidities, hive_co2_levels, hive_weights
 * Confirm exact table/column names with Dev C before running php artisan migrate.
 */
class SensorDataController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FarmerHiveAccessService $hiveAccess)
    {
    }

    /** REQ-F-FAPI-14 */
    public function temperature(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $hive = $this->ownedHive($request, $hiveId);
        $data = $this->buildQuery($hive->temperatures(), $request)->paginate($request->perPage());

        return $this->success($data);
    }

    /** REQ-F-FAPI-15 */
    public function humidity(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $hive = $this->ownedHive($request, $hiveId);
        $data = $this->buildQuery($hive->humidities(), $request)->paginate($request->perPage());

        return $this->success($data);
    }

    /** REQ-F-FAPI-16 */
    public function carbonDioxide(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $hive = $this->ownedHive($request, $hiveId);
        $data = $this->buildQuery($hive->carbondioxides(), $request)->paginate($request->perPage());

        return $this->success($data);
    }

    /** REQ-F-FAPI-17 */
    public function weight(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $hive = $this->ownedHive($request, $hiveId);
        $data = $this->buildQuery($hive->weights(), $request)->paginate($request->perPage());

        return $this->success($data);
    }

    /**
     * REQ-F-FAPI-18: Latest reading aggregated from all 4 sensors.
     * Returns a single object — no pagination needed.
     */
    public function latest(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $hive = $this->ownedHive($request, $hiveId);

        return $this->success([
            'hive_id'      => $hiveId,
            'temperature'  => $hive->temperatures()->latest()->first(),
            'humidity'     => $hive->humidities()->latest()->first(),
            'co2'          => $hive->carbondioxides()->latest()->first(),
            'weight'       => $hive->weights()->latest()->first(),
            'fetched_at'   => now()->toISOString(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Shared: apply optional date range filter to any sensor relationship query
    // -------------------------------------------------------------------------
    private function buildQuery($query, SensorDataRequest $request)
    {
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        return $query->orderByDesc('created_at');
    }

    private function ownedHive(SensorDataRequest $request, int $hiveId)
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        return $this->hiveAccess->findOwnedHive($farmer, $hiveId);
    }
}
