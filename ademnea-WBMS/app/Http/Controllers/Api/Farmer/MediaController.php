<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Services\Farmer\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Get photos for a hive
     */
    public function photos(Request $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $perPage = $request->input('per_page', 8);
        $photos = $this->mediaService->getPhotos($farmer, $hiveId, $perPage);

        // Add full URLs to photos
        $items = $photos->items();
        foreach ($items as $photo) {
            $photo->url = asset('storage/' . $photo->path);
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $photos->currentPage(),
                'last_page' => $photos->lastPage(),
                'per_page' => $photos->perPage(),
                'total' => $photos->total(),
            ],
        ]);
    }

    /**
     * Get audio recordings for a hive
     */
    public function audio(Request $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $audio = $this->mediaService->getAudio($farmer, $hiveId);

        // Add full URLs to audio files
        foreach ($audio as &$item) {
            $item['url'] = asset('storage/' . $item['path']);
        }

        return response()->json([
            'data' => $audio,
        ]);
    }

    /**
     * Get videos for a hive
     */
    public function videos(Request $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $perPage = $request->input('per_page', 8);
        $videos = $this->mediaService->getVideos($farmer, $hiveId, $perPage);

        // Add full URLs to videos
        $items = $videos->items();
        foreach ($items as $video) {
            $video->url = asset('storage/' . $video->path);
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ],
        ]);
    }
}