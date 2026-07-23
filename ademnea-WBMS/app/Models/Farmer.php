<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Farmer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // Core identity
        'first_name',
        'last_name',
        'email',

        // Phone – both column names accepted (phone_number is the original DB
        // column; phone is the model-preferred alias added by migration).
        'phone',
        'phone_number',
        'phone_secondary',

        // Location
        'country',
        'region',
        'village',

        // Identity documents
        'national_id',
        'id_document_path',
        'photo_path',

        // Status columns
        'status',           // enum: Active | Inactive | Suspended  (added by migration)
        'profile_status',   // active | pending | incomplete
        'is_active',        // original boolean column – kept for API backward compat

        // Farmer code (original DB column)
        'farmer_code',

        // Timestamps
        'registration_date',
        'last_login_at',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'deleted_at'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Farmer $farmer): void {
            if (empty($farmer->farmer_code)) {
                $farmer->farmer_code = static::generateFarmerCode(
                    $farmer->country ?? 'UG'
                );
            }
        });
    }

    /**
     * Generate a unique farmer code in the format: {COUNTRY}{10-char alphanum}
     * e.g. UG4a9f2c1b3d — matches the pattern of existing codes like CM0092u32eu8uew.
     */
    public static function generateFarmerCode(string $country = 'UG'): string
    {
        $prefix = strtoupper(substr($country, 0, 2));

        do {
            $code = $prefix . strtolower(Str::random(10));
        } while (static::withTrashed()->where('farmer_code', $code)->exists());

        return $code;
    }

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
