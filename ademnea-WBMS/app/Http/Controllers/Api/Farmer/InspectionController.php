<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Services\Farmer\InspectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InspectionController extends Controller
{
    use ResolvesFarmer;

    protected InspectionService $inspectionService;

    public function __construct(InspectionService $inspectionService)
    {
        $this->inspectionService = $inspectionService;
    }

    /**
     * Get inspection records for a hive
     */
    public function index(Request $request, int $hiveId): JsonResponse
    {
        $farmer = $this->farmer($request);

        $perPage = $request->input('per_page', 25);
        $inspections = $this->inspectionService->getInspections($farmer, $hiveId, $perPage);

        return response()->json([
            'data' => $inspections->items(),
            'meta' => [
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'per_page' => $inspections->perPage(),
                'total' => $inspections->total(),
            ],
        ]);
    }
}
