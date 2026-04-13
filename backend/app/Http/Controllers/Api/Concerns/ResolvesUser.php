<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesUser
{
    protected function user(Request $request): User
    {
        return $request->attributes->get('user');
    }
}
