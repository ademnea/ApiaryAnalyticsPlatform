<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\Inspection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InspectionService
{
    public function __construct(private readonly FarmerHiveAccessService $hiveAccess)
    {
    }
    /**
     * Get inspection records for a hive
     */
    public function getInspections(Farmer $farmer, int $hiveId, int $perPage = 25): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        return Inspection::where('hive_id', $hiveId)
            ->orderByDesc('inspected_at')
            ->paginate($perPage);
    }

    /**
     * Verify hive ownership
     */
    private function verifyHiveOwnership(Farmer $farmer, int $hiveId): void
    {
        $this->hiveAccess->findOwnedHive($farmer, $hiveId);
    }
}
