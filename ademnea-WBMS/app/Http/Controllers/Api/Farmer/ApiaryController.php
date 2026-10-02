<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ClampsPageSize;
use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Services\Farmer\ApiaryDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiaryController extends Controller
{
    use ClampsPageSize, ResolvesFarmer;

    public function __construct(private readonly ApiaryDataService $apiaryData)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $farmer = $this->farmer($request);

        return response()->json($this->paginated($this->apiaryData->getApiaries($farmer, $this->pageSize($request, 25))));
    }

    public function hives(Request $request, int $apiaryId): JsonResponse
    {
        $farmer = $this->farmer($request);

        return response()->json($this->paginated($this->apiaryData->getHives($farmer, $apiaryId, $this->pageSize($request, 25))));
    }

    private function paginated($paginator): array
    {
        return ['data' => $paginator->items(), 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
        ]];
    }
}
