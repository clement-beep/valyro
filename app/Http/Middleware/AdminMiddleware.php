<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $adminEmail = (string) config('valyro.admin.email', 'maillet.clement.ifsi@gmail.com');

        if (empty($adminEmail) || ($user->email ?? null) !== $adminEmail) {
            abort(403);
        }

        return $next($request);
    }
}
