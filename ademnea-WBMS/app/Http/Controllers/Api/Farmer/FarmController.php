<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Services\Farmer\FarmDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FarmController extends Controller
{
    protected FarmDataService $farmDataService;

    public function __construct(FarmDataService $farmDataService)
    {
        $this->farmDataService = $farmDataService;
    }

    /**
     * Get all farms for the authenticated farmer
     */
    public function index(Request $request): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $perPage = $request->input('per_page', 25);
        $farms = $this->farmDataService->getFarms($farmer, $perPage);

        return response()->json([
            'data' => $farms->items(),
            'meta' => [
                'current_page' => $farms->currentPage(),
                'last_page' => $farms->lastPage(),
                'per_page' => $farms->perPage(),
                'total' => $farms->total(),
            ],
        ]);
    }

    /**
     * Get hives for a specific farm
     */
    public function hives(Request $request, int $farmId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $perPage = $request->input('per_page', 25);
        $hives = $this->farmDataService->getHives($farmer, $farmId, $perPage);

        return response()->json([
            'data' => $hives->items(),
            'meta' => [
                'current_page' => $hives->currentPage(),
                'last_page' => $hives->lastPage(),
                'per_page' => $hives->perPage(),
                'total' => $hives->total(),
            ],
        ]);
    }

    /**
     * Get a single hive
     */
    public function showHive(Request $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $hive = $this->farmDataService->getHive($farmer, $hiveId);

        return response()->json([
            'data' => $hive,
        ]);
    }
}