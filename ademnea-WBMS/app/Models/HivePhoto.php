<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Contracts\MediaUploadStorageContract;

class HivePhoto extends Model
{
    protected $table = 'hive_photos';

    public $timestamps = false;

    protected $fillable = [
        'hive_id', 'device_id', 'file_path', 's3_object_key',
        'file_size_bytes', 'recorded_at', 'created_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
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

    /**
     * Resolves file_path to a fully-qualified HTTPS URL, so no controller
     * ever manually builds this string. Delegates to whichever storage
     * transport is currently bound (local mock disk today, S3 once live)
     * — this accessor doesn't change across that swap.
     */
    public function getUrlAttribute(): string
    {
        return app(MediaUploadStorageContract::class)->publicUrl($this->file_path);
    }
}