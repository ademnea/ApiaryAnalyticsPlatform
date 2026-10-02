<?php

namespace App\Services\Farmer;

use App\Contracts\MediaUploadStorageContract;
use App\Models\Farmer;
use App\Models\HiveAudio;
use App\Models\HivePhoto;
use App\Models\HiveVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Photos, audio and video captured by a farmer's hive devices, newest first,
 * each with a `url` from the active media storage (S3 signed URL or local mock).
 */
class MediaService
{
    public function __construct(
        private readonly FarmerHiveAccessService $hiveAccess,
        private readonly MediaUploadStorageContract $storage,
    ) {
    }

    public function getPhotos(Farmer $farmer, int $hiveId, int $perPage = 8): LengthAwarePaginator
    {
        return $this->paginate(HivePhoto::class, $farmer, $hiveId, $perPage);
    }

    public function getAudio(Farmer $farmer, int $hiveId, int $perPage = 8): LengthAwarePaginator
    {
        return $this->paginate(HiveAudio::class, $farmer, $hiveId, $perPage);
    }

    public function getVideos(Farmer $farmer, int $hiveId, int $perPage = 8): LengthAwarePaginator
    {
        return $this->paginate(HiveVideo::class, $farmer, $hiveId, $perPage);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function paginate(string $model, Farmer $farmer, int $hiveId, int $perPage): LengthAwarePaginator
    {
        $this->hiveAccess->findOwnedHive($farmer, $hiveId);

        $page = $model::where('hive_id', $hiveId)
            ->latest('recorded_at')
            ->paginate($perPage);

        $page->getCollection()->each(function (Model $item) {
            $item->setAttribute('url', $this->storage->publicUrl($item->s3_object_key ?: $item->file_path));
        });

        return $page;
    }
}
