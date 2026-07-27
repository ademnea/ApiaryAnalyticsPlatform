<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farmer_id',
        'name',
        'district',
        'address',
        'latitude',
        'longitude',
        'description',
    ];

    protected $casts = [
        'farmer_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function hives(): HasMany
    {
        return $this->hasMany(Hive::class);
    }
}