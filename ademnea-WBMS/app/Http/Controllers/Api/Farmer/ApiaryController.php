<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Services\Farmer\ApiaryDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiaryController extends Controller
{
    public function __construct(private readonly ApiaryDataService $apiaryData)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json($this->paginated($this->apiaryData->getApiaries($farmer, (int) $request->input('per_page', 25))));
    }

    public function hives(Request $request, int $apiaryId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json($this->paginated($this->apiaryData->getHives($farmer, $apiaryId, (int) $request->input('per_page', 25))));
    }

    private function paginated($paginator): array
    {
        return ['data' => $paginator->items(), 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
        ]];
    }
}
