<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'platform_id',
        'source',
        'username',
        'first_name',
        'last_name',
        'avatar_url',
        'is_premium',
        'language_code',
        'level',
        'experience',
        'energy',
        'energy_updated_at',
        'coins',
        'referral_code',
        'referred_by',
        'last_activity',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'energy_updated_at' => 'datetime',
        'last_activity' => 'datetime',
        'level' => 'integer',
        'experience' => 'integer',
        'energy' => 'integer',
        'coins' => 'integer',
    ];

    public static function generateReferralCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function generators(): HasMany
    {
        return $this->hasMany(Generator::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->orders()->where('status', 'active');
    }

    public function chests(): HasMany
    {
        return $this->hasMany(Chest::class);
    }

    public function characterRelationships(): HasMany
    {
        return $this->hasMany(CharacterRelationship::class);
    }

    public function eventProgress(): HasMany
    {
        return $this->hasMany(EventProgress::class);
    }

    public function streak(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Streak::class);
    }

    public function decorItems(): HasMany
    {
        return $this->hasMany(DecorItem::class);
    }

    public function adViews(): HasMany
    {
        return $this->hasMany(AdView::class);
    }

    public function gameState(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(GameState::class);
    }

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }
}
