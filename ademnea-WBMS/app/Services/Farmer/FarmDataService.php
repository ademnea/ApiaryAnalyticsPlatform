<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\Farm;
use App\Models\Hive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FarmDataService
{
    /**
     * Get all farms for a farmer
     */
    public function getFarms(Farmer $farmer, int $perPage = 25): LengthAwarePaginator
    {
        return Farm::where('farmer_id', $farmer->id)
            ->withCount('hives')
            ->paginate($perPage);
    }

    /**
     * Get all hives for a specific farm
     */
    public function getHives(Farmer $farmer, int $farmId, int $perPage = 25): LengthAwarePaginator
    {
        // Verify farm belongs to farmer
        $farm = Farm::where('id', $farmId)
            ->where('farmer_id', $farmer->id)
            ->firstOrFail();

        return Hive::where('farm_id', $farmId)
            ->withCount(['temperatures', 'humidities', 'carbondioxides', 'weights'])
            ->paginate($perPage);
    }

    /**
     * Get a single hive with ownership verification
     */
    public function getHive(Farmer $farmer, int $hiveId): Hive
    {
        $hive = Hive::where('id', $hiveId)
            ->whereHas('farm', function ($query) use ($farmer) {
                $query->where('farmer_id', $farmer->id);
            })
            ->firstOrFail();

        return $hive;
    }

    /**
     * Verify hive ownership
     */
    public function verifyHiveOwnership(Farmer $farmer, int $hiveId): bool
    {
        return Hive::where('id', $hiveId)
            ->whereHas('farm', function ($query) use ($farmer) {
                $query->where('farmer_id', $farmer->id);
            })
            ->exists();
    }
}