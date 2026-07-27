<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * STUB — REQ-F-FAPI-05 (view/edit profile).
 * Not yet implemented. Exists so routes/farmer_api.php resolves cleanly.
 */
class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }
}