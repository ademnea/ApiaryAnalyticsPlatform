<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\Hive;
use App\Models\BeehiveInspection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InspectionService
{
    /**
     * Get inspection records for a hive
     */
    public function getInspections(Farmer $farmer, int $hiveId, int $perPage = 25): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        return BeehiveInspection::where('hive_id', (string) $hiveId)
            ->orderBy('date', 'desc')
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