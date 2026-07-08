<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HivePhoto;
use App\Models\HiveAudio;
use App\Models\HiveVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MediaService
{
    /**
     * Get photos for a hive
     */
    public function getPhotos(Farmer $farmer, int $hiveId, int $perPage = 8): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        return HivePhoto::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get audio recordings for a hive
     */
    public function getAudio(Farmer $farmer, int $hiveId, int $limit = 20): array
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        return HiveAudio::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get videos for a hive
     */
    public function getVideos(Farmer $farmer, int $hiveId, int $perPage = 8): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        return HiveVideo::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Verify hive ownership
     */
    private function verifyHiveOwnership(Farmer $farmer, int $hiveId): void
    {
        Hive::where('id', $hiveId)
            ->whereHas('farm', function ($query) use ($farmer) {
                $query->where('farmer_id', $farmer->id);
            })
            ->firstOrFail();
    }
}