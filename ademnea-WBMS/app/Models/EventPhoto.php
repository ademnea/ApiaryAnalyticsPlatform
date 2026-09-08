<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPhoto extends Model
{
    use HasFactory;

    protected $table = 'event_photos';

    protected $fillable = [
        'event_id',
        'photo_path',
        'photo_filename',
        'photo_order',
    ];

    protected $casts = [
        'photo_order' => 'integer',
        'created_at' => 'datetime',
    ];

    protected $appends = ['photo_url'];

    const UPDATED_AT = null;

    /**
     * Relationship: Parent event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get photo URL attribute
     */
    public function getPhotoUrlAttribute(): string
    {
        return asset('storage/' . $this->photo_path);
    }
}
