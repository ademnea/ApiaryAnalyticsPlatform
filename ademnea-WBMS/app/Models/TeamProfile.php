<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TeamProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'role',
        'institution',
        'biography',
        'research_interests',
        'email',
        'phone',
        'profile_photo',
        'status',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Profile Photo URL
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? Storage::disk('public')->url($this->profile_photo)
            : null;
    }

    /**
     * Scope Published Profiles
     */
    public function scopePublished($query)
    {
        return $query
            ->where('status', 'Published')
            ->orderBy('display_order');
    }

    /**
     * Status Badge Color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Published' => 'success',
            'Draft' => 'warning',
            default => 'danger',
        };
    }
}