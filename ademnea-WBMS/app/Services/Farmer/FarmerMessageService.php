<?php

namespace App\Services\Farmer;

use App\Models\FarmerMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * REQ-F-FAPI-31, 32: Farmer-to-admin messaging.
 * Used by MessageController::store() and ::index().
 */
class FarmerMessageService
{
    public function __construct(
        private readonly FarmerAuditService $audit
    ) {}

    public function submit(int $farmerId, array $data): FarmerMessage
    {
        $message = FarmerMessage::create([
            'farmer_id' => $farmerId,
            'hive_id'   => $data['hive_id'] ?? null,
            'subject'   => $data['subject'],
            'message'   => $data['message'],
            'status'    => 'sent',
        ]);

        $this->audit->log($farmerId, 'message_submitted', $message->id);

        return $message;
    }

    public function listForFarmer(int $farmerId, int $perPage = 15): LengthAwarePaginator
    {
        return FarmerMessage::where('farmer_id', $farmerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}