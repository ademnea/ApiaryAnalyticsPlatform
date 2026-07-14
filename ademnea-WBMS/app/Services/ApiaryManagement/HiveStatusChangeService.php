<?php

namespace App\Services\ApiaryManagement;

use App\Exceptions\ApiaryManagement\InvalidHiveStatusTransitionException;
use App\Models\Hive;
use App\Models\HiveStatusHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HiveStatusChangeService
{
    public function __construct(private HiveRegistrationService $registrationService)
    {
    }

    public function updateStatus(Hive $hive, string $newStatus, $changedBy, ?string $changeNotes): Hive
    {
        $currentStatus = $hive->current_status;

        if (! $this->validateStatusTransition($currentStatus, $newStatus)) {
            throw InvalidHiveStatusTransitionException::notAllowed($currentStatus, $newStatus);
        }

        return DB::transaction(function () use ($hive, $newStatus, $changedBy, $changeNotes) {
            $hive->update(['current_status' => $newStatus]);

            HiveStatusHistory::create([
                'hive_id'           => $hive->id,
                'previous_status'   => $currentStatus,
                'new_status'        => $newStatus,
                'reason_note'       => $changeNotes,
                'changed_by_user_id'=> $changedBy->id ?? $changedBy,
                'transitioned_at'   => now(),
            ]);

            return $hive->fresh();
        });
    }

    public function getStatusHistory(Hive $hive): Collection
    {
        return $hive->statusHistory()->latest('transitioned_at')->get();
    }

    public function getAllowedNextStatuses(Hive $hive): array
    {
        return $this->validateTransitionsFrom($hive->current_status);
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

    private function validateTransitionsFrom(string $currentStatus): array
    {
        $map = [
            'Active' => ['Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned'],
            'Inactive' => ['Active', 'Decommissioned'],
            'Under Inspection' => ['Active', 'Queenless', 'Absconded', 'Decommissioned'],
            'Queenless' => ['Active', 'Under Inspection', 'Absconded', 'Decommissioned'],
            'Absconded' => ['Active', 'Decommissioned'],
            'Decommissioned' => [],
        ];

        return $map[$currentStatus] ?? [];
    }
}
