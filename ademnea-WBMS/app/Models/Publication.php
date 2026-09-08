<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Publication extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'publications';

    protected $fillable = [
        'slug',
        'author',
        'title',
        'publisher',
        'publication_year',
        'description',
        'attachment_path',
        'attachment_filename',
        'image_path',
        'image_filename',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['image_url', 'attachment_url'];

    /**
     * Relationship: Creator user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Get only published publications
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: Order by most recent publication date, then by publication year
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc')
                     ->orderBy('publication_year', 'desc');
    }

    /**
     * Scope: Get all publications for admin (including soft deleted)
     */
    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->withTrashed();
    }

    /**
     * Publish this publication
     */
    public function publish(): bool
    {
        return $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish this publication
     */
    public function unpublish(): bool
    {
        return $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Check if attachment exists
     */
    public function hasAttachment(): bool
    {
        return !is_null($this->attachment_path) && Storage::exists($this->attachment_path);
    }

    /**
     * Generate slug from author and title
     */
    public static function generateSlug(string $author, string $title): string
    {
        return Str::slug($author . '-' . $title) . '-' . uniqid();
    }

    /**
     * Get image URL attribute
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    /**
     * Get attachment URL attribute
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? route('publications.download', $this->id) : null;
    }
}
