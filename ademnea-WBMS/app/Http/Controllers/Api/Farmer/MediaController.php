<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Services\Farmer\MediaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REQ-F-FAPI-19 to 21: hive media, paginated with ?per_page= (max 50).
 */
class MediaController extends Controller
{
    use ApiResponse;
    use ResolvesFarmer;

    public function __construct(private readonly MediaService $mediaService)
    {
    }

    public function photos(Request $request, int $hiveId): JsonResponse
    {
        return $this->paginated($this->mediaService->getPhotos($this->currentFarmer($request), $hiveId, $this->perPage($request)));
    }

    public function audio(Request $request, int $hiveId): JsonResponse
    {
        return $this->paginated($this->mediaService->getAudio($this->currentFarmer($request), $hiveId, $this->perPage($request)));
    }

    public function videos(Request $request, int $hiveId): JsonResponse
    {
        return $this->paginated($this->mediaService->getVideos($this->currentFarmer($request), $hiveId, $this->perPage($request)));
    }

    private function perPage(Request $request): int
    {
        return max(1, min((int) $request->input('per_page', 8), 50));
    }
}
