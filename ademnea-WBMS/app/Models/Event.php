<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'events';

    protected $fillable = [
        'slug',
        'title',
        'venue',
        'description',
        'event_date',
        'event_time',
        'article_link',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['primary_photo_url', 'article_link_display'];

    /**
     * Relationship: Creator user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Event photos
     */
    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class)
                    ->orderBy('photo_order', 'asc');
    }

    /**
     * Scope: Get only published events
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: Order by most recent event date first
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('event_date', 'desc');
    }

    /**
     * Scope: Get upcoming events
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('event_date', '>=', today());
    }

    /**
     * Scope: Get past events
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('event_date', '<', today());
    }

    /**
     * Scope: Get all events for admin (including soft deleted)
     */
    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->withTrashed();
    }

    /**
     * Publish this event
     */
    public function publish(): bool
    {
        return $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish this event
     */
    public function unpublish(): bool
    {
        return $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Check if event has photos
     */
    public function hasPhotos(): bool
    {
        return $this->photos()->exists();
    }

    /**
     * Get primary photo (first in gallery)
     */
    public function getPrimaryPhoto(): ?EventPhoto
    {
        return $this->photos()->orderBy('photo_order')->first();
    }

    /**
     * Generate slug from title
     */
    public static function generateSlug(string $title): string
    {
        return Str::slug($title) . '-' . uniqid();
    }

    /**
     * Get primary photo URL attribute
     */
    public function getPrimaryPhotoUrlAttribute(): ?string
    {
        $photo = $this->getPrimaryPhoto();
        return $photo ? asset('storage/' . $photo->photo_path) : null;
    }

    /**
     * Get article link display attribute
     */
    public function getArticleLinkDisplayAttribute(): ?string
    {
        return $this->article_link ?: null;
    }
}
