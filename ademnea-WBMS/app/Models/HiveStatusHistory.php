<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HiveStatusHistory extends Model
{
    protected $fillable = [
        'hive_id',
        'previous_status',
        'new_status',
        'reason_note',
        'changed_by_user_id',
        'transitioned_at',
        'created_at',
    ];

    protected $casts = [
        'transitioned_at' => 'datetime',
        'created_at'      => 'datetime',
    ];

    public function hive()
    {
        return $this->belongsTo(Hive::class);
    }

    public function changedBy()
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
}
