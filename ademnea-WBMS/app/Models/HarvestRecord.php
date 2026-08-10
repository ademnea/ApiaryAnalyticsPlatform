<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HarvestRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'hive_id',
        'harvested_by',
        'harvest_date',
        'honey_yield_kg',
        'beeswax_yield_kg',
        'notes',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'honey_yield_kg' => 'decimal:2',
        'beeswax_yield_kg' => 'decimal:2',
    ];

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public function harvester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'harvested_by');
    }
}
