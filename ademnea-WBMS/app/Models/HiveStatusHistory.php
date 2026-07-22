<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveStatusHistory extends Model
{
    protected $table = 'hive_status_histories';

    public $timestamps = false;

    protected $fillable = [
        'hive_id',
        'previous_status',
        'new_status',
        'reason_note',
        'changed_by_user_id',
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
