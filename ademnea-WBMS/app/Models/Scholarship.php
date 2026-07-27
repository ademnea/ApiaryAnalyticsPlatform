<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Scholarship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'institution',
        'country',
        'category',
        'funding_type',
        'funding_amount',
        'currency',
        'description',
        'eligibility',
        'benefits',
        'application_procedure',
        'banner_image',
        'status',
        'is_featured',
        'application_deadline',
        'application_link',
    ];

    protected $casts = [
        'funding_amount' => 'decimal:2',
        'is_featured' => 'boolean',
        'application_deadline' => 'date',
    ];

    public function attachments(): HasMany
    {
        return $this->hasMany(ScholarshipAttachment::class);
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner_image ? Storage::disk('public')->url($this->banner_image) : null;
    }
}
