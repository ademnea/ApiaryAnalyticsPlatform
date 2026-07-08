<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hive extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'latitude',
        'longitude',
        'status',
        'connected',
        'colonized',
        'type',
        'installation_date',
        'colonization_date',
        'bee_species',
        'notes',
    ];

    protected $casts = [
        'farm_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'connected' => 'boolean',
        'colonized' => 'boolean',
        'installation_date' => 'date',
        'colonization_date' => 'date',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function temperatures(): HasMany
    {
        return $this->hasMany(HiveTemperature::class);
    }

    public function humidities(): HasMany
    {
        return $this->hasMany(HiveHumidity::class);
    }

    public function carbondioxides(): HasMany
    {
        return $this->hasMany(HiveCarbondioxide::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(HiveWeight::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HivePhoto::class);
    }

    public function audios(): HasMany
    {
        return $this->hasMany(HiveAudio::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(HiveVideo::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(BeehiveInspection::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FarmerMessage::class);
    }
}