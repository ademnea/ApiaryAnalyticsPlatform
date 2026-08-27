<?php

namespace App\Contracts;

use App\Models\Hive;
use Illuminate\Database\Eloquent\Collection;

interface HiveStatusChangeServiceContract
{
    public function updateStatus(Hive $hive, string $newStatus, $changedBy, ?string $changeNotes): Hive;
    public function getStatusHistory(Hive $hive): Collection;
    public function getAllowedNextStatuses(Hive $hive): array;
    public function validateStatusTransition(string $currentStatus, string $newStatus): bool;
}
