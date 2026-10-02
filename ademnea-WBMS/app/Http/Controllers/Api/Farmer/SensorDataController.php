<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\SensorDataRequest;
use App\Models\Hive;
use App\Services\Farmer\FarmerHiveAccessService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;

/**
 * REQ-F-FAPI-14 to 18: sensor readings for a hive the farmer owns.
 *
 *   GET /api/v1/farmer/hives/{hiveId}/temperature|humidity|carbondioxide|weight
 *   GET /api/v1/farmer/hives/{hiveId}/latest    ← newest reading of each sensor
 */
class SensorDataController extends Controller
{
    use ApiResponse;
    use ResolvesFarmer;

    public function __construct(private readonly FarmerHiveAccessService $hiveAccess)
    {
    }

    public function temperature(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        return $this->readings($this->ownedHive($request, $hiveId)->temperatures(), $request);
    }

    public function humidity(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        return $this->readings($this->ownedHive($request, $hiveId)->humidities(), $request);
    }

    public function carbonDioxide(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        return $this->readings($this->ownedHive($request, $hiveId)->carbondioxides(), $request);
    }

    public function weight(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        return $this->readings($this->ownedHive($request, $hiveId)->weights(), $request);
    }

    public function latest(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $hive = $this->ownedHive($request, $hiveId);

        return $this->success([
            'hive_id' => $hive->id,
            'temperature' => $hive->temperatures()->latest('recorded_at')->first(),
            'humidity' => $hive->humidities()->latest('recorded_at')->first(),
            'co2' => $hive->carbondioxides()->latest('recorded_at')->first(),
            'weight' => $hive->weights()->latest('recorded_at')->first(),
            'fetched_at' => now()->toIso8601String(),
        ]);
    }

    private function readings(HasMany $query, SensorDataRequest $request): JsonResponse
    {
        if ($request->filled('from')) {
            $query->where('recorded_at', '>=', $request->date('from')->utc());
        }
        if ($request->filled('to')) {
            $query->where('recorded_at', '<=', $request->date('to')->utc());
        }

        return $this->paginated($query->latest('recorded_at')->paginate($request->perPage()));
    }

    private function ownedHive(SensorDataRequest $request, int $hiveId): Hive
    {
        return $this->hiveAccess->findOwnedHive($this->currentFarmer($request), $hiveId);
    }
}
