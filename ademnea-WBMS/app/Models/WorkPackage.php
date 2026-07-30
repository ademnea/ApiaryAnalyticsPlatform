<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class WorkPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wp_number',
        'title',
        'summary',
        'description',
        'objectives',
        'deliverables',
        'lead',
        'partners',
        'featured_image',
        'status',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_PUBLISHED = 'Published';
    public const STATUS_ARCHIVED = 'Archived';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image
            ? Storage::disk('public')->url($this->featured_image)
            : null;
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}