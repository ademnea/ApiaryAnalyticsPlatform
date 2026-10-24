<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Contracts\MediaUploadStorageContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveAudio extends Model
{
    protected $table = 'hive_audios';

    public $timestamps = false;

    protected $fillable = [
        'hive_id', 'device_id', 'file_path', 's3_object_key',
        'file_size_bytes', 'duration_seconds', 'recorded_at', 'created_at',
    ];

    protected $casts = [
        'recorded_at' => UtcDateTime::class,
        'created_at' => 'datetime',
    ];

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }

    public function getUrlAttribute(): string
    {
        return app(MediaUploadStorageContract::class)->publicUrl($this->file_path);
    }
}
