<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureJwtAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!session('jwt_token') || !session('user')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
