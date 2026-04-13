<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('admin_authenticated')) {
            if ($request->is('admin/login')) {
                return $next($request);
            }
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
