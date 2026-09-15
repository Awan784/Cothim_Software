<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()
            ?? $request->user('salesman');

        if ($user?->organization_id) {
            app()->instance('current_organization_id', (int) $user->organization_id);
            $org = $user->relationLoaded('organization')
                ? $user->organization
                : \App\Models\Organization::query()->find($user->organization_id);
            if ($org) {
                app()->instance('current_organization', $org);
            }
        }

        return $next($request);
    }
}
