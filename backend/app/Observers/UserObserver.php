<?php

namespace App\Observers;

use App\Models\User;
use App\Services\GameInitService;

class UserObserver
{
    public function created(User $user): void
    {
        app(GameInitService::class)->seedStarterGenerators($user);
    }
}
