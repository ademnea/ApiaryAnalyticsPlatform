<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Apiary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'country',
        'region',
        'district',
        'farmer_id',
        'hive_capacity',
        'description',
        'status',
    ];

    protected $casts = [
        'hive_capacity' => 'integer',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function getCountryNameAttribute(): string
    {
        return config("countries.{$this->country}", $this->country);
    }

    public function hives(): HasMany
    {
        return $this->hasMany(Hive::class);
    }

    // TODO: Uncomment when HarvestRecord model is implemented
    // public function harvestRecords(): HasMany
    // {
    //     return $this->hasMany(HarvestRecord::class);
    // }

    // TODO: Uncomment when Inspection model is implemented
    // public function inspections(): HasManyThrough
    // {
    //     return $this->hasManyThrough(Inspection::class, Hive::class);
    // }

    // TODO: Uncomment when HarvestRecord model is implemented
    // public function getTotalSeasonalYield(?int $year = null): float
    // {
    //     $query = $this->harvestRecords();
    //
    //     if ($year) {
    //         $query->whereYear('harvest_date', $year);
    //     }
    //
    //     return (float) $query->sum('honey_yield_kg');
    // }

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

    public function hiveCount(): int
    {
        return $this->hives()->count();
    }

    public function activeHiveCount(): int
    {
        return $this->hives()->where('current_status', 'Active')->count();
    }
}
