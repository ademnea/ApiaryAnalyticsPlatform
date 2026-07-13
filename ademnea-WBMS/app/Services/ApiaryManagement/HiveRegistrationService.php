<?php

namespace App\Services\ApiaryManagement;

use App\Events\ApiaryManagement\HiveRegistered;
use App\Models\Apiary;
use App\Models\Hive;
use App\Models\HiveStatusHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class HiveRegistrationService
{
    public function register(Apiary $apiary, array $data): Hive
    {
        if (!$apiary->status === 'Active') {
            throw new \Exception('Cannot register hive under an inactive apiary.');
        }

        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $this->validateGpsCoordinates($data['latitude'], $data['longitude']);
        }

        $hive = new Hive($data);
        $hive->apiary_id = $apiary->id;
        $hive->current_status = 'Active';
        $hive->save();

        event(new HiveRegistered($hive));

        return $hive;
    }

    public function createHive(Apiary $apiary, array $data): Hive
    {
        return $this->register($apiary, $data);
    }

    public function generateHiveCode(Apiary $apiary): string
    {
        return $this->generateHybridIdentifier($apiary);
    }

    public function generateHybridIdentifier(Apiary $apiary): string
    {
        return DB::transaction(function () use ($apiary) {
            $lockedApiary = Apiary::where('id', $apiary->id)->lockForUpdate()->first();

            if (! $lockedApiary || ! $lockedApiary->apiary_code) {
                throw new RuntimeException(
                    "Apiary #{$apiary->id} has no apiary_code — cannot generate a hive identifier."
                );
            }

            $prefix = "HIVE-{$lockedApiary->country}-{$lockedApiary->apiary_code}-";

            $lastSequence = Hive::withTrashed()
                ->where('apiary_id', $lockedApiary->id)
                ->where('hybrid_identifier', 'like', $prefix.'%')
                ->pluck('hybrid_identifier')
                ->map(fn ($identifier) => (int) Str::after($identifier, $prefix))
                ->max() ?? 0;

            $nextSequence = $lastSequence + 1;

            return $prefix.str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
        });
    }

    public function update(Hive $hive, array $data): Hive
    {
        if (
            (!empty($data['latitude']) || !empty($data['longitude']))
            && ($data['latitude'] !== $hive->latitude || $data['longitude'] !== $hive->longitude)
        ) {
            $this->validateGpsCoordinates(
                $data['latitude'] ?? $hive->latitude,
                $data['longitude'] ?? $hive->longitude
            );
        }

        $hive->update($data);

        return $hive->fresh();
    }

    public function updateHive(Hive $hive, array $data): Hive
    {
        return $this->update($hive, $data);
    }

    public function list(?Apiary $apiary = null, array $filters = []): LengthAwarePaginator
    {
        $query = Hive::query()->with(['apiary', 'statusHistory']);

        if ($apiary) {
            $query->where('apiary_id', $apiary->id);
        }

        if (!empty($filters['apiary_id'])) {
            $query->where('apiary_id', $filters['apiary_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        if (!empty($filters['needs_inspection'])) {
            $query->needingInspection();
        }

        return $query->orderBy('hybrid_identifier')->paginate(20);
    }

    public function findByCode(string $code): ?Hive
    {
        return Hive::where('hybrid_identifier', $code)->first();
    }

    public function getHiveWithAllData(int $hiveId): Hive
    {
        return Hive::with([
            'apiary',
            'statusHistory',
            'deviceAssignments',
            'inspections',
            'harvestRecords',
            'alertThresholds',
        ])->findOrFail($hiveId);
    }

    public function deleteHive(Hive $hive): bool
    {
        return (bool) $hive->delete();
    }

    public function changeHiveStatus(Hive $hive, string $newStatus, ?string $note, ?int $userId): Hive
    {
        $currentStatus = $hive->current_status;

        if (! $this->validateStatusTransition($currentStatus, $newStatus)) {
            throw new \InvalidArgumentException("Hive cannot transition from \"{$currentStatus}\" to \"{$newStatus}\".");
        }

        return DB::transaction(function () use ($hive, $currentStatus, $newStatus, $note, $userId) {
            $hive->update(['current_status' => $newStatus]);

            HiveStatusHistory::create([
                'hive_id'           => $hive->id,
                'previous_status'   => $currentStatus,
                'new_status'        => $newStatus,
                'reason_note'       => $note,
                'changed_by_user_id'=> $userId,
                'transitioned_at'   => now(),
            ]);

            return $hive->fresh();
        });
    }

    public function validateStatusTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        $transitions = [
            'Active' => ['Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned'],
            'Inactive' => ['Active', 'Decommissioned'],
            'Under Inspection' => ['Active', 'Queenless', 'Absconded', 'Decommissioned'],
            'Queenless' => ['Active', 'Under Inspection', 'Absconded', 'Decommissioned'],
            'Absconded' => ['Active', 'Decommissioned'],
            'Decommissioned' => [],
        ];

        return in_array($newStatus, $transitions[$currentStatus] ?? [], true);
    }

    public function getHivesByApiary(int $apiaryId, array $filters = []): Collection
    {
        $query = Hive::byApiary($apiaryId);

        if (! empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        return $query->get();
    }

    public function getHiveLocation(int $hiveId): array
    {
        $hive = Hive::select('latitude', 'longitude')->findOrFail($hiveId);

        return ['latitude' => $hive->latitude, 'longitude' => $hive->longitude];
    }

    private function validateGpsCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('GPS coordinates are out of valid geographic range.');
        }
    }
}
