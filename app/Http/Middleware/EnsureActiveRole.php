<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $expected = Role::tryFrom($role);

        abort_if($expected === null, 404);

        $user = $request->user();

        if ($user === null || $user->active_role !== $expected || ! $user->hasRole($expected)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
