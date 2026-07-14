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

    public function getCountryNameAttribute(): string
    {
        return config("countries.{$this->country}", $this->country);
    }

    // TODO: Uncomment when Inspection model is implemented
    // public function inspections(): HasMany
    // {
    //     return $this->hasMany(Inspection::class);
    // }

    // TODO: Uncomment when HarvestRecord model is implemented
    // public function harvestRecords(): HasMany
    // {
    //     return $this->hasMany(HarvestRecord::class);
    // }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Human-readable, disambiguating label for <select> dropdowns.
     *
     * Multiple farmers can share a name, so we append the unique national ID
     * (or phone fallback) plus location. Selection still happens by the
     * primary key; this only prevents a human from picking the wrong row.
     */
    public function getSelectLabelAttribute(): string
    {
        $label = $this->full_name;

        $parts = array_filter([
            $this->village,
            $this->region,
        ]);

        if ($this->national_id) {
            $parts[] = "Nat.ID {$this->national_id}";
        } elseif ($this->phone) {
            $parts[] = $this->phone;
        }

        if (! empty($parts)) {
            $label .= ' — ' . implode(', ', $parts);
        }

        return $label;
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
