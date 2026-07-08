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
        'farmer_id' => 'integer',
        'affected_record_id' => 'integer',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}