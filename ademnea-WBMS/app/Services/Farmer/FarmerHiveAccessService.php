<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\Hive;

class FarmerHiveAccessService
{
    public function findOwnedHive(Farmer $farmer, int $hiveId): Hive
    {
        return Hive::query()->whereKey($hiveId)
            ->whereHas('apiary', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->firstOrFail();
    }
}
