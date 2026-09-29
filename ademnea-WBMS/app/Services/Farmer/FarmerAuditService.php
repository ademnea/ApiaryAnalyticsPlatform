<?php

namespace App\Services\Farmer;

use App\Models\FarmerAuditLog;

/**
 * REQ-F-FAPI-38: Write-only audit trail for farmer-initiated actions.
 * Used by AuthService (registration, profile updates, device token registration).
 */
class FarmerAuditService
{
    public function log(
        int $farmerId,
        string $actionType,
        ?int $affectedRecordId = null,
        string $affectedRecordType = 'farmer'
    ): void {
        FarmerAuditLog::record($farmerId, $actionType, $affectedRecordId, $affectedRecordType);
    }
}
