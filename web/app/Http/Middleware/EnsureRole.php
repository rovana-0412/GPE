<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = session('user', []);
        $role = data_get($user, 'role_slug', data_get($user, 'role'));
        $roleSlug = Str::slug((string) $role);

        abort_unless(in_array($roleSlug, $roles, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
