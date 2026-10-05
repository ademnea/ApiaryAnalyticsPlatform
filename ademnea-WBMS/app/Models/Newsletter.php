<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Newsletter extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'content', 'image_path', 'image_filename',
        'is_published', 'published_at', 'view_count', 'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['image_url', 'excerpt'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNull('deleted_at');
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('created_at');
    }

    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->withTrashed();
    }

    public function publish(): bool
    {
        return $this->update(['is_published' => true, 'published_at' => now()]);
    }

    public function unpublish(): bool
    {
        return $this->update(['is_published' => false, 'published_at' => null]);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public static function generateSlug(string $title): string
    {
        return Str::slug($title) . '-' . uniqid();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function getExcerptAttribute(): string
    {
        return Str::limit(strip_tags($this->content), 150);
    }

    public function getSanitizedContentAttribute(): string
    {
        return strip_tags($this->content, '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><blockquote>');
    }
}
