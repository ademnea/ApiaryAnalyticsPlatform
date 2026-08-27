<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

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

    public static function record(int $farmerId, string $actionType, ?int $affectedRecordId = null): self
    {
        return static::create([
            'farmer_id'          => $farmerId,
            'action_type'        => $actionType,
            'affected_record_id' => $affectedRecordId,
            'created_at'         => now(),
        ]);
    }
}
