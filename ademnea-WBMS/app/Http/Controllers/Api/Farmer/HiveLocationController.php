<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\UpdateHiveLocationRequest;
use App\Services\ApiaryManagement\HiveRegistrationService;
use App\Services\Farmer\FarmerAuditService;
use App\Services\Farmer\FarmerHiveAccessService;
use Illuminate\Http\JsonResponse;

/**
 *   PUT /api/v1/farmer/hives/{hive_id}/location
 *
 * Lets a farmer with the farmer-write role record where one of their own
 * hives stands, using the phone's location. Reading the position back needs
 * no endpoint of its own: latitude, longitude and accuracy_meters are part of
 * every hive in GET /apiaries/{apiaryId}/hives.
 */
class HiveLocationController extends Controller
{
    use ResolvesFarmer;

    public function __construct(
        private readonly FarmerHiveAccessService $hiveAccess,
        private readonly HiveRegistrationService $hives,
        private readonly FarmerAuditService $audit,
    ) {
    }

    public function update(UpdateHiveLocationRequest $request, int $hiveId): JsonResponse
    {
        $farmer = $this->farmer($request);
        $hive = $this->hiveAccess->findOwnedHive($farmer, $hiveId);

        $hive = $this->hives->updateHive($hive, [
            'latitude'        => $request->validated('latitude'),
            'longitude'       => $request->validated('longitude'),
            // Absent means unknown, so an older fix's accuracy is not kept for a new position.
            'accuracy_meters' => $request->validated('accuracy_meters'),
        ]);

        $this->audit->log($farmer->id, 'hive_location_updated', $hive->id, 'hive');

        return response()->json([
            'message' => 'Hive location updated.',
            'data' => [
                'hive_id'         => $hive->id,
                'latitude'        => (float) $hive->latitude,
                'longitude'       => (float) $hive->longitude,
                'accuracy_meters' => $hive->accuracy_meters !== null ? (float) $hive->accuracy_meters : null,
            ],
        ]);
    }
}
