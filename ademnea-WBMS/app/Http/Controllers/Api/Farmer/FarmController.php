<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * STUB — REQ-F-FAPI-06 (farmer's farms, read-only).
 * Depends on Farm model (Dev B — Apiary Management). Not yet implemented.
 */
class FarmController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }
}