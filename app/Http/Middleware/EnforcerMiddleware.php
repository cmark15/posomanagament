<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnforcerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('enforcer')->user();

        // Not logged in → go to enforcer login
        if (!$user) {
            return redirect()
                ->route('enforcer.showLogin')
                ->with('error', 'You must be logged in to access the page.');
        }

        // Logged in BUT inactive (soft-deleted) → force logout + redirect
        if (method_exists($user, 'trashed') && $user->trashed()) {
            Auth::guard('enforcer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('enforcer.showLogin')
                ->withErrors(['badge_num' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}
