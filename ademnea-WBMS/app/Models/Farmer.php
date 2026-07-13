<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    use SoftDeletes;

    // SECURITY: Use $guarded instead of $fillable
    protected $guarded = [
        'id',
        'farmer_code',      // Never mass-assign code
        'user_id',          // Never mass-assign user link
        'is_active',        // Never mass-assign state
        'profile_status',   // Never mass-assign status
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Append computed attributes to model serialisation.
     * full_name is used in dashboard views and API responses.
     */
    protected $appends = ['full_name'];

    /**
     * Relationship: A farmer optionally has a linked user account.
     * Nullable because farmer registration can happen before user account creation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A farmer has many apiaries (NEW).
     */
    public function apiaries(): HasMany
    {
        return $this->hasMany(Apiary::class, 'farmer_id');
    }

    /**
     * Relationship: A farmer has many inspection records.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    /**
     * Relationship: A farmer has many harvest records.
     */
    public function harvestRecords(): HasMany
    {
        return $this->hasMany(HarvestRecord::class);
    }

    /**
     * Scope: Get only active farmers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by country.
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope: Filter by profile status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('profile_status', $status);
    }

    /**
     * Get farmer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}