<?php

namespace App\Services\ApiaryManagement;

use App\Contracts\ApiaryDirectoryServiceContract;
use App\Models\Apiary;
use App\Models\Hive;
use App\Models\IotDevice;
use Illuminate\Support\Collection;

class ApiaryDirectoryService implements ApiaryDirectoryServiceContract
{
    public function listApiariesWithPrimaryFarmer(): Collection
    {
        return Apiary::query()
            ->active()
            ->with(['farmer:id,first_name,last_name'])
            ->get(['id', 'name', 'country', 'farmer_id'])
            ->map(function (Apiary $apiary) {
                return (object) [
                    'id'          => $apiary->id,
                    'name'        => $apiary->name,
                    'farmer_name' => $apiary->farmer?->full_name ?? 'Unassigned',
                    'country'     => $apiary->country,
                ];
            })
            ->values();
    }

    public function listHivesAvailableForDeviceType(int $apiaryId, string $deviceType): Collection
    {
        $hivesWithActiveDevice = IotDevice::query()
            ->where('device_type', $deviceType)
            ->where('active_flag', true)
            ->whereNotNull('hive_id')
            ->pluck('hive_id');

        return Hive::query()
            ->where('apiary_id', $apiaryId)
            ->whereNotIn('id', $hivesWithActiveDevice)
            ->orderBy('hybrid_identifier')
            ->get(['id', 'hybrid_identifier', 'display_name'])
            ->map(function (Hive $hive) {
                return (object) [
                    'id'           => $hive->id,
                    'hybrid_code'  => $hive->hybrid_identifier,
                    'display_name' => $hive->display_name,
                ];
            })
            ->values();
    }
}
