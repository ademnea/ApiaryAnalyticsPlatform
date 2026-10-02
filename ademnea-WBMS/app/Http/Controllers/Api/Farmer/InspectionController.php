<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Services\Farmer\InspectionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REQ-F-FAPI-22: inspection records for a hive the farmer owns.
 */
class InspectionController extends Controller
{
    use ApiResponse;
    use ResolvesFarmer;

    public function __construct(private readonly InspectionService $inspectionService)
    {
    }

    public function index(Request $request, int $hiveId): JsonResponse
    {
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        return $this->paginated(
            $this->inspectionService->getInspections($this->currentFarmer($request), $hiveId, $perPage)
        );
    }
}
