<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventProgress extends Model
{
    protected $table = 'event_progress';

    protected $fillable = [
        'user_id', 'event_id', 'score', 'milestones_claimed',
    ];

    protected $casts = [
        'score' => 'integer',
        'milestones_claimed' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
