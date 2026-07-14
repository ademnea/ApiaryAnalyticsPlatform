<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\FarmerMessage;
use App\Models\FarmerAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageService
{
    /**
     * Submit a message to admin
     */
    public function submitMessage(Farmer $farmer, array $data): FarmerMessage
    {
        // If hive_id is provided, verify ownership
        if (isset($data['hive_id']) && $data['hive_id']) {
            $this->verifyHiveOwnership($farmer, $data['hive_id']);
        }

        $message = FarmerMessage::create([
            'farmer_id' => $farmer->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'hive_id' => $data['hive_id'] ?? null,
            'status' => 'sent',
        ]);

        // Log the action
        FarmerAuditLog::create([
            'farmer_id' => $farmer->id,
            'action_type' => 'message_submitted',
            'affected_record_type' => 'farmer_message',
            'affected_record_id' => $message->id,
        ]);

        // TODO: Send notification to admin

        return $message;
    }

    /**
     * Get all messages for a farmer
     */
    public function getMessages(Farmer $farmer, int $perPage = 25): LengthAwarePaginator
    {
        return FarmerMessage::where('farmer_id', $farmer->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Verify hive ownership
     */
    private function verifyHiveOwnership(Farmer $farmer, int $hiveId): void
    {
        \App\Models\Hive::where('id', $hiveId)
            ->whereHas('farm', function ($query) use ($farmer) {
                $query->where('farmer_id', $farmer->id);
            })
            ->firstOrFail();
    }
}