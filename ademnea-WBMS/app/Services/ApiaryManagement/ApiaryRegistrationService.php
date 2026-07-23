<?php

namespace App\Services\ApiaryManagement;

use App\Exceptions\ApiaryManagement\ApiaryDeactivationException;
use App\Models\Apiary;
use App\Models\Farmer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiaryRegistrationService
{
    public function register(array $data): Apiary
    {
        if (!empty($data['farmer_id'])) {
            $this->assertFarmerIsAssignable($data['farmer_id']);
        }

        $data['country'] = $data['country'] ?? 'UG';
        $data['status'] = $data['status'] ?? 'Active';

        return DB::transaction(function () use ($data) {
            if (empty($data['apiary_code'])) {
                $data['apiary_code'] = $this->generateApiaryCode($data['name'], $data['country']);
            }

            $apiary = new Apiary($data);
            $apiary->save();

            return $apiary;
        });
    }

    public function createApiary(array $data): Apiary
    {
        $data['country'] = $data['country'] ?? 'UG';
        $data['status'] = $data['status'] ?? 'Active';

        return DB::transaction(function () use ($data) {
            $data['apiary_code'] = $this->generateApiaryCode($data['name'], $data['country']);

            $apiary = new Apiary($data);
            $apiary->save();

            return $apiary;
        });
    }

    public function updateApiary(Apiary $apiary, array $data): Apiary
    {
        $isDeactivating = isset($data['status'])
            && $data['status'] !== 'Active'
            && $apiary->status === 'Active';

        if ($isDeactivating) {
            $activeHiveCount = $apiary->hives()->where('current_status', 'Active')->count();

            if ($activeHiveCount > 0) {
                throw ApiaryDeactivationException::hasActiveHives($apiary->id, $activeHiveCount);
            }
        }

        $apiary->update($data);

        return $apiary->fresh();
    }

    public function update(Apiary $apiary, array $data): Apiary
    {
        if (array_key_exists('farmer_id', $data) && $data['farmer_id'] !== null) {
            $this->assertFarmerIsAssignable($data['farmer_id']);
        }

        $apiary->update($data);

        return $apiary;
    }

    public function deleteApiary(Apiary $apiary): bool
    {
        return DB::transaction(function () use ($apiary) {
            $apiary->hives()->get()->each->delete();

            return (bool) $apiary->delete();
        });
    }

    public function getApiaryWithHives(int $apiaryId, array $filters = []): Apiary
    {
        return Apiary::with(['hives' => function ($query) use ($filters) {
            if (! empty($filters['status'])) {
                $query->where('current_status', $filters['status']);
            }
            // TODO: Uncomment when DeviceAssignment model is implemented
            // $query->withCount('deviceAssignments');
        }, 'farmer'])->findOrFail($apiaryId);
    }

    public function getApiaryStatistics(Apiary $apiary, ?int $year = null): array
    {
        return [
            'hive_count' => $apiary->hives()->count(),
            'active_hive_count' => $apiary->hives()->where('current_status', 'Active')->count(),
            // 'device_count' => $apiary->hives()
            //     ->withCount('deviceAssignments')
            //     ->get()
            //     ->sum('device_assignments_count'),
            // 'seasonal_yield_kg' => $apiary->getTotalSeasonalYield($year),
            // 'inspection_count' => $apiary->hives()
            //     ->withCount('inspections')
            //     ->get()
            //     ->sum('inspections_count'),
        ];
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Apiary::query()->with(['farmer', 'hives']);

        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['farmer_id'])) {
            $query->where('farmer_id', $filters['farmer_id']);
        }

        if (!empty($filters['unassigned'])) {
            $query->unassigned();
        }

        return $query->orderBy('country')->orderBy('name')->paginate(15);
    }

    public function find(int $id): Apiary
    {
        return Apiary::with(['farmer', 'hives.statusHistory'])->findOrFail($id);
    }

    public function assignableFarmers(): \Illuminate\Support\Collection
    {
        return Farmer::active()->orderBy('first_name')->orderBy('last_name')->get();
    }

    public function deactivate(Apiary $apiary): void
    {
        $apiary->status = 'Inactive';
        $apiary->save();
    }

    private function assertFarmerIsAssignable(int $farmerId): void
    {
        $farmer = Farmer::find($farmerId);

        if (!$farmer || $farmer->status !== 'Active') {
            throw ValidationException::withMessages([
                'farmer_id' => 'The selected farmer is not active or does not exist.',
            ]);
        }
    }

    private function generateApiaryCode(string $name, string $country): string
    {
        return ApiaryCodeGenerator::generate($name, $country);
    }
}
