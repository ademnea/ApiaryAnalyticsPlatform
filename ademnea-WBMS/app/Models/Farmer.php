<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_secondary',
        'country',
        'region',
        'village',
        'national_id',
        'id_document_path',
        'photo_path',
        'status',
        'registration_date',
        'last_login_at',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'last_login_at'     => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    public function apiaries(): HasMany
    {
        return $this->hasMany(Apiary::class, 'farmer_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function harvestRecords(): HasMany
    {
        return $this->hasMany(HarvestRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
