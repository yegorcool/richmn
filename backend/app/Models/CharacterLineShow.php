<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterLineShow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'character_line_id', 'shown_count', 'last_shown_at',
    ];

    protected $casts = [
        'shown_count' => 'integer',
        'last_shown_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function characterLine(): BelongsTo
    {
        return $this->belongsTo(CharacterLine::class);
    }
}
