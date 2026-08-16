<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        try {
            $required = Permission::from($permission);
        } catch (ValueError) {
            abort(403);
        }

        $user = $request->user();

        if ($user?->hasPermission($required)) {
            return $next($request);
        }

        if ($user) {
            return redirect()->route($user->homeRouteName());
        }

        abort(403);
    }
}
