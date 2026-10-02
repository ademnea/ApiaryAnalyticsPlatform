<?php

namespace App\Services\Farmer;

use App\Models\FarmerAuditLog;

/**
 * REQ-F-FAPI-38: Write-only audit trail for farmer-initiated actions.
 * Used by FarmerMessageService (message submissions).
 */
class FarmerAuditService
{
    public function log(int $farmerId, string $actionType, string $affectedRecordType, ?int $affectedRecordId = null): void
    {
        FarmerAuditLog::record($farmerId, $actionType, $affectedRecordType, $affectedRecordId);
    }
}