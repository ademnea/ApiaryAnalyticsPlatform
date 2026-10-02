<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'action_type',
        'affected_record_type',
        'affected_record_id',
        'details',
    ];

    protected $casts = [
        'farmer_id'          => 'integer',
        'affected_record_id' => 'integer',
        'created_at'         => 'datetime',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    /**
     * `affected_record_type` is NOT NULL in the schema, so it must always be
     * supplied — it defaults to 'farmer' because that is what the overwhelming
     * majority of farmer-initiated actions touch.
     */
    public static function record(
        int $farmerId,
        string $actionType,
        ?int $affectedRecordId = null,
        string $affectedRecordType = 'farmer'
    ): self {
        return static::create([
            'farmer_id'            => $farmerId,
            'action_type'          => $actionType,
            'affected_record_type' => $affectedRecordType,
            'affected_record_id'   => $affectedRecordId,
        ]);
    }
}
