<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * STUB — REQ-F-FAPI-22 (hive inspection records, read-only).
 * Depends on BeehiveInspection model (Dev B — Apiary Management). Not yet implemented.
 */
class InspectionController extends Controller
{
    use ApiResponse;

    public function index(Request $request, int $hive_id): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }
}