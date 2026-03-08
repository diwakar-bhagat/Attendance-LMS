<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRole
{
    /**
     * Handle an incoming request.
     * Prevents cross-role access (e.g., student accessing /admin)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // 1. Must be logged in
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Soft-deleted / inactive accounts must be booted instantly. Ensures instant revocation.
        if (!$user->is_active || $user->trashed()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // 3. User role must match one of the allowed parameters
        if (!$user->role || !in_array($user->role->name, $roles)) {
            abort(403, 'Unauthorized Access: You do not have the required role to view this page.');
        }

        return $next($request);
    }
}