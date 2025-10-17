<?php

namespace Plugins\AdminOnly\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictToAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || empty($user->is_admin)) {
            abort(403, 'Admins only.');
        }

        return $next($request);
    }
}