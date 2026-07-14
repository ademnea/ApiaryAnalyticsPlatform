<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['feedback_id','file_name','file_path','file_type'];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }
}
