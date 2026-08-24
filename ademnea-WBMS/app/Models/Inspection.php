<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inspection extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'hive_id',
        'inspected_by',
        'inspected_at',
        'strength_rating',
        'disease_events',
        'queen_status_notes',
        'general_notes',
    ];

    protected $casts = [
        'inspected_at' => 'date',
    ];

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
