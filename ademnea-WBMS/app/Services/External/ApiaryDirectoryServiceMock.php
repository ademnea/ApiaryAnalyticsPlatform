<?php

namespace App\Services\External;

use App\Contracts\ApiaryDirectoryServiceContract;
use Illuminate\Support\Collection;

/**
 * TEMPORARY — hardcoded sample data standing in for the real Apiary
 * Management service. Replace the binding in AppServiceProvider once
 * Developer B delivers the real implementation; nothing else changes.
 */
class ApiaryDirectoryServiceMock implements ApiaryDirectoryServiceContract
{
    public function listApiariesWithPrimaryFarmer(): Collection
    {
        return collect([
            (object) ['id' => 1, 'name' => 'Mukono Central Apiary', 'farmer_name' => 'Nakato Prossy', 'country' => 'Uganda'],
            (object) ['id' => 2, 'name' => 'Jinja Riverside Apiary', 'farmer_name' => 'Okello Simon', 'country' => 'Uganda'],
            (object) ['id' => 3, 'name' => 'Yei Community Apiary', 'farmer_name' => 'Amina Deng', 'country' => 'South Sudan'],
        ]);
    }

    public function listHivesAvailableForDeviceType(int $apiaryId, string $deviceType): Collection
    {
        // Mock does not filter by $deviceType — the real implementation
        // must exclude hives already carrying an active device of this type.
        return match ($apiaryId) {
            1 => collect([
                (object) ['id' => 101, 'hybrid_code' => 'HIVE-UG-MUK-001', 'display_name' => 'Queen Colony A'],
                (object) ['id' => 102, 'hybrid_code' => 'HIVE-UG-MUK-002', 'display_name' => 'Queen Colony B'],
            ]),
            2 => collect([
                (object) ['id' => 201, 'hybrid_code' => 'HIVE-UG-JIN-001', 'display_name' => 'Riverside Hive 1'],
            ]),
            default => collect(),
        };
    }
}