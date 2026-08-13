<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Services\Farmer\FarmDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REQ-F-FAPI-07 (hives under a farm, read-only, with geocoordinates).
 */
class HiveController extends Controller
{
    public function __construct(private readonly FarmDataService $farmDataService)
    {
    }

    public function index(Request $request, int $farm_id): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();
        $hives = $this->farmDataService->getHives(
            $farmer,
            $farm_id,
            (int) $request->input('per_page', 25),
        );

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
}
