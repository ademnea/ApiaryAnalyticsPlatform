<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveStatusHistory extends Model
{
    // Explicit table name — overrides Laravel's default pluralization
    // (would guess "hive_status_histories"; actual table is "hive_status_history")
    protected $table = 'hive_status_history';

    // Disable automatic update of updated_at since this is an audit log
    public $timestamps = false;

    protected $fillable = [
        'hive_id',
        'previous_status',
        'new_status',
        'changed_by_user_id',
        'change_notes',
        'reason_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relationship: Status history belongs to a hive.
     */
    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    /**
     * Relationship: Status history is linked to the user who made the change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Scope: Get history for a specific hive in reverse chronological order.
     */
    public function scopeForHive($query, int $hiveId)
    {
        return $query->where('hive_id', $hiveId)->latest('created_at');
    }

    /**
     * Scope: Get all changes made by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by_user_id', $userId)->latest('created_at');
    }

    /**
     * Scope: Get all status transitions of a specific type (e.g., all → decommissioned).
     */
    public function scopeTransitionTo($query, string $newStatus)
    {
        return $query->where('new_status', $newStatus);
    }
}
