<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyLog extends Model
{
    protected $fillable = ['user_id', 'type', 'amount', 'source'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
