<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'farmer_id',
        'action_type',
        'affected_record_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    /**
     * Convenience writer so service classes don't repeat ::create() calls.
     * Usage: FarmerAuditLog::record($farmerId, 'profile_update', $farmer->id);
     */
    public static function record(int $farmerId, string $actionType, ?int $affectedRecordId = null): self
    {
        return static::create([
            'farmer_id'           => $farmerId,
            'action_type'         => $actionType,
            'affected_record_id'  => $affectedRecordId,
        ]);
    }
}
