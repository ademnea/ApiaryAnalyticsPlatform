<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * STUB — REQ-F-FAPI-07 (hives under a farm, read-only, with geocoordinates).
 * Depends on Hive model (Dev B — Apiary Management). Not yet implemented.
 */
class HiveController extends Controller
{
    use ApiResponse;

    public function index(Request $request, int $farm_id): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }
}