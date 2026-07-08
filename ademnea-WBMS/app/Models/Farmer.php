<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Farmer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telephone',
        'address',
        'gender',
        'fcm_token',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    public function hives(): HasManyThrough
    {
        return $this->hasManyThrough(Hive::class, Farm::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FarmerMessage::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(FarmerAuditLog::class);
    }
}