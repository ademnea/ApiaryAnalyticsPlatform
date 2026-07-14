<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryAlbum extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'Monitoring',
        'Events',
        'Research',
        'Programs',
        'Conferences',
    ];

    public const VISIBILITY_OPTIONS = [
        'public' => 'Public',
        'private' => 'Private',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'category',
        'visibility',
        'is_published',
        'cover_image',
        'views',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('order')->orderBy('id');
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null;
    }

    public static function booted(): void
    {
        static::creating(function (GalleryAlbum $album): void {
            if (empty($album->slug)) {
                $album->slug = static::generateUniqueSlug($album->title);
            }
        });

        static::updating(function (GalleryAlbum $album): void {
            if (empty($album->slug)) {
                $album->slug = static::generateUniqueSlug($album->title, $album->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = sprintf('%s-%d', $original, ++$count);
        }

        return $slug;
    }
}
