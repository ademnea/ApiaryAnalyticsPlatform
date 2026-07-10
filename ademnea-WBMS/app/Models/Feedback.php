<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'feedback';

    protected $fillable = [
        'feedback_category_id',
        'full_name',
        'email',
        'phone',
        'organization',
        'subject',
        'message',
        'status',
        'submitted_at',
        'admin_response',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeedbackCategory::class, 'feedback_category_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FeedbackAttachment::class);
    }
}
