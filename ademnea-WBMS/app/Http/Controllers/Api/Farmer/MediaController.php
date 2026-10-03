<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Contracts\MediaUploadStorageContract;
use App\Http\Controllers\Api\Farmer\Concerns\ClampsPageSize;
use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Services\Farmer\MediaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UC-FAPI-10 to 12: hive photos, audio and video.
 *
 * `url` is resolved through MediaUploadStorageContract::publicUrl(), which is
 * what knows how to turn a stored object key into something fetchable — a
 * 6-hour presigned S3 URL in production, a local disk URL against the mock.
 * It was previously built as asset('storage/' . $media->path); there is no
 * `path` attribute on these models, so every url came back as "/storage/".
 */
class MediaController extends Controller
{
    use ClampsPageSize, ResolvesFarmer;

    public function __construct(
        private readonly MediaService $mediaService,
        private readonly MediaUploadStorageContract $storage,
    ) {}

    public function photos(Request $request, int $hiveId): JsonResponse
    {
        return $this->paginated(
            $this->mediaService->getPhotos(
                $this->farmer($request),
                $hiveId,
                $this->pageSize($request, 8, 50)
            )
        );
    }

    public function audio(Request $request, int $hiveId): JsonResponse
    {
        return $this->paginated(
            $this->mediaService->getAudio(
                $this->farmer($request),
                $hiveId,
                $this->pageSize($request, 8, 50)
            )
        );
    }

    public function videos(Request $request, int $hiveId): JsonResponse
    {
        return $this->paginated(
            $this->mediaService->getVideos(
                $this->farmer($request),
                $hiveId,
                $this->pageSize($request, 8, 50)
            )
        );
    }

    /**
     * One shape for all three media types. Audio previously returned a bare
     * list of at most 20 items with no meta and ignored per_page, forcing the
     * client to special-case it.
     */
    private function paginated(LengthAwarePaginator $page): JsonResponse
    {
        $items = collect($page->items())->each(function ($media) {
            $media->url = $media->file_path
                ? $this->storage->publicUrl($media->file_path)
                : null;
        });

        return response()->json([
            'data' => $items->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
            ],
        ]);
    }
}
