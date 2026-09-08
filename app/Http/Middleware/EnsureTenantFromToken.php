<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFromToken extends \Illuminate\Auth\Middleware\Authenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        // Authenticate the request first
        $this->authenticate($request, $guards);

        // Extract tenant_id from JWT claims
        $user = auth('api')->user();

        if ($user) {
            // Set tenant_id in request for middleware chain
            $request->merge([
                'tenant_id' => $user->tenant_id,
                'authenticated_user' => $user,
            ]);

            // Set in auth context
            $request->attributes->set('tenant_id', $user->tenant_id);
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null; // Return 401 for API
    }
}