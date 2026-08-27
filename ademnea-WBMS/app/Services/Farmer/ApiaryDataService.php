<?php

namespace App\Services\Farmer;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiaryDataService
{
    public function getApiaries(Farmer $farmer, int $perPage = 25): LengthAwarePaginator
    {
        return Apiary::query()->where('farmer_id', $farmer->id)->withCount('hives')->orderBy('name')->paginate($perPage);
    }

    public function getHives(Farmer $farmer, int $apiaryId, int $perPage = 25): LengthAwarePaginator
    {
        $this->findOwnedApiary($farmer, $apiaryId);

        return Hive::query()->where('apiary_id', $apiaryId)
            ->withCount(['temperatures', 'humidities', 'carbondioxides', 'weights'])
            ->orderBy('hybrid_identifier')->paginate($perPage);
    }

    public function findOwnedApiary(Farmer $farmer, int $apiaryId): Apiary
    {
        return Apiary::whereKey($apiaryId)->where('farmer_id', $farmer->id)->firstOrFail();
    }
}
