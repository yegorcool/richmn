<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Streak extends Model
{
    protected $fillable = [
        'user_id', 'current_streak', 'last_login_date',
        'longest_streak', 'reward_claimed_today',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_login_date' => 'date',
        'reward_claimed_today' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkIn(): void
    {
        $today = now()->toDateString();

        if ($this->last_login_date?->toDateString() === $today) {
            return;
        }

        $yesterday = now()->subDay()->toDateString();
        if ($this->last_login_date?->toDateString() === $yesterday) {
            $this->current_streak++;
        } else {
            $this->current_streak = 1;
        }

        $this->longest_streak = max($this->longest_streak, $this->current_streak);
        $this->last_login_date = $today;
        $this->reward_claimed_today = false;
        $this->save();
    }

    public function getStreakDay(): int
    {
        return (($this->current_streak - 1) % 7) + 1;
    }
}
