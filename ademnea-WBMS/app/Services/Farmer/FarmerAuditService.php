<?php

namespace App\Services\Farmer;

use App\Models\FarmerAuditLog;

/**
 * REQ-F-FAPI-38: Write-only audit trail for farmer-initiated actions.
 * Used by FarmerAuthService (profile updates) and AlertController (device token registration).
 */
class FarmerAuditService
{
    public function log(int $farmerId, string $actionType, ?int $affectedRecordId = null): void
    {
        FarmerAuditLog::record($farmerId, $actionType, $affectedRecordId);
    }
}