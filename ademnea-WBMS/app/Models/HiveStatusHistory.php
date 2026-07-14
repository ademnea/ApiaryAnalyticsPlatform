<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveStatusHistory extends Model
{
    // Explicit table name — overrides Laravel's default pluralization
    protected $table = 'hive_status_history';

    public $timestamps = false;

    protected $fillable = [
        'hive_id',
        'previous_status',
        'new_status',
        'changed_by_user_id',
        'change_notes',
        'reason_code',
        'reason_note',
        'transitioned_at',
        'created_at',
    ];

    protected $casts = [
        'transitioned_at' => 'datetime',
        'created_at'      => 'datetime',
    ];

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function scopeForHive($query, int $hiveId)
    {
        return $query->where('hive_id', $hiveId)->latest('transitioned_at');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by_user_id', $userId)->latest('transitioned_at');
    }

    public function scopeTransitionTo($query, string $newStatus)
    {
        return $query->where('new_status', $newStatus);
    }
}
