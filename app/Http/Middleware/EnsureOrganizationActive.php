<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isPlatformAdmin() || $request->routeIs('logout')) {
            return $next($request);
        }

        $org = $user->organization;
        if ($org && $org->status !== 'active') {
            return response()->view('auth.suspended', ['organization' => $org], 403);
        }

        return $next($request);
    }
}
