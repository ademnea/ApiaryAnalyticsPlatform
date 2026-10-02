<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Services\Farmer\ApiaryDataService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The farmer's apiaries and the hives in each (ownership: apiary.farmer_id).
 */
class ApiaryController extends Controller
{
    use ApiResponse;
    use ResolvesFarmer;

    public function __construct(private readonly ApiaryDataService $apiaryData)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->apiaryData->getApiaries($this->currentFarmer($request), $this->perPage($request))
        );
    }

    public function hives(Request $request, int $apiaryId): JsonResponse
    {
        return $this->paginated(
            $this->apiaryData->getHives($this->currentFarmer($request), $apiaryId, $this->perPage($request))
        );
    }

    private function perPage(Request $request): int
    {
        return max(1, min((int) $request->input('per_page', 25), 100));
    }
}
