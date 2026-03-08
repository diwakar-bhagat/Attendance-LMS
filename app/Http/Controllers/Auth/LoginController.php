<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request safely.
     * Prevents session fixation, checks active status, and routes by role.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $credentials['is_active'] = true; // Auto-fail login if deactivated

        // Auth::attempt handles securely comparing hashes internally
        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            // SECURITY-CRITICAL: Prevent session fixation
            $request->session()->regenerate();

            $user = Auth::user();

            // Register tenant in the session for global scope injection
            $request->session()->put('institution_id', $user->institution_id);

            // Send users to their distinct modular boundaries
            return match ($user->role->name) {
                    'admin' => redirect()->intended(route('admin.dashboard')),
                    'faculty' => redirect()->intended(route('faculty.dashboard')),
                    'student' => redirect()->intended(route('student.dashboard')),
                    default => redirect('/'),
                };
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our active records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session securely.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}