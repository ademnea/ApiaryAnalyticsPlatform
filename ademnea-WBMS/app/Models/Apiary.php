<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Apiary extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'country',
        'region',
        'district',
        'farmer_id',
        'description',
        'managing_entity',
        'status',
        'apiary_code',
    ];

    protected $casts = [
        //
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function getCountryNameAttribute(): string
    {
        return config("countries.{$this->country}", $this->country);
    }

    /**
     * Disambiguating label for <select> dropdowns.
     *
     * Apiary names are not unique across farmers, so we append the owner and
     * location. Selection still happens by the primary key.
     */
    public function getSelectLabelAttribute(): string
    {
        $label = "{$this->name} ({$this->country})";

        $owner = $this->farmer?->full_name;
        $location = implode(', ', array_filter([$this->region, $this->district]));

        $suffix = implode(' · ', array_filter([$owner, $location]));

        if ($suffix !== '') {
            $label .= " — {$suffix}";
        }

        return $label;
    }

    public function hives(): HasMany
    {
        return $this->hasMany(Hive::class);
    }

    public function inspections(): HasManyThrough
    {
        return $this->hasManyThrough(Inspection::class, Hive::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByFarmer($query, int $farmerId)
    {
        return $query->where('farmer_id', $farmerId);
    }

    public function scopeForFarmer($query, Farmer $farmer)
    {
        return $query->where('farmer_id', $farmer->id);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('farmer_id');
    }
}
