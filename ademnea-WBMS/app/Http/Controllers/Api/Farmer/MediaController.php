<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * STUB — REQ-F-FAPI-19 to 21 (hive photos, audio, videos, read-only).
 * Not yet implemented.
 */
class MediaController extends Controller
{
    use ApiResponse;

    public function photos(Request $request, int $hive_id): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }

    public function audio(Request $request, int $hive_id): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }

    public function videos(Request $request, int $hive_id): JsonResponse
    {
        return $this->error('Not implemented yet.', 501);
    }
}