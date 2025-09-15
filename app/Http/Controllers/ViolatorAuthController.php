<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationData;
use Illuminate\Validation\ValidationException;

class ViolatorAuthController extends Controller
{
    //
    public function showLogin()
    {
        return view("auth.violatorLogin");
    }
    //
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $maxAttempts   = (int) env('AUTH_MAX_FAILED_LOGINS', 5);
        $baseLockMins  = (int) env('AUTH_LOCKOUT_MINUTES', 15);
        $useExpBackoff = filter_var(env('AUTH_LOCKOUT_EXP_BACKOFF', false), FILTER_VALIDATE_BOOL);
        $maxLockMins   = (int) env('AUTH_LOCKOUT_MAX_MINUTES', 1440);

        // Find violator (include soft-deleted to show proper message if needed)
        $violator = \App\Models\Violator::withTrashed()
            ->where('username', $validated['username'])
            ->first();

        if (! $violator) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'credentials' => 'Incorrect username or password.',
            ]);
        }

        if (method_exists($violator, 'trashed') && $violator->trashed()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'credentials' => 'Your account is deactivated. Please contact the Admin.',
            ]);
        }

        // If currently locked
        if ($violator->lockout_until && now()->lt($violator->lockout_until)) {
            $remaining = now()->diffInSeconds($violator->lockout_until);
            session()->flash('lockout_remaining', $remaining);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'credentials' => 'Account temporarily locked. Try again later.',
            ]);
        }

        $plain = $validated['password'];

        // Check real password
        if (\Illuminate\Support\Facades\Hash::check($plain, $violator->password)) {
            // Success → reset counters/lock
            $violator->failed_attempts = 0;
            $violator->lockout_until   = null;
            $violator->lockouts_count  = 0;
            $violator->save();

            \Illuminate\Support\Facades\Auth::guard('violator')->login($violator);
            $request->session()->regenerate();

            return redirect()->route('violator.dashboard')->with('login_success', true);
        }

        // Check default password (first-time login)
        if ($violator->defaultPassword && \Illuminate\Support\Facades\Hash::check($plain, $violator->defaultPassword)) {
            // On default password success, also reset counters/lock
            $violator->failed_attempts = 0;
            $violator->lockout_until   = null;
            $violator->lockouts_count  = 0;
            $violator->save();

            \Illuminate\Support\Facades\Auth::guard('violator')->login($violator);
            $request->session()->regenerate();

            session()->flash('must_change_password', true);
            return redirect()->route('violator.password.change');
        }

        // Wrong password → increment and possibly lock
        $violator->failed_attempts = (int) $violator->failed_attempts + 1;

        if ($violator->failed_attempts >= $maxAttempts) {
            $lockMinutes = $baseLockMins;
            if ($useExpBackoff) {
                $lockMinutes = min($baseLockMins * (2 ** (int) $violator->lockouts_count), $maxLockMins);
            }

            $violator->lockout_until   = now()->addMinutes($lockMinutes);
            $violator->lockouts_count  = (int) $violator->lockouts_count + 1;
            $violator->failed_attempts = 0; // reset streak after locking
            $violator->save();

            session()->flash('lockout_remaining', now()->diffInSeconds($violator->lockout_until));
            throw \Illuminate\Validation\ValidationException::withMessages([
                'credentials' => "Too many attempts. Account locked for {$lockMinutes} minute(s).",
            ]);
        }

        $violator->save();

        throw \Illuminate\Validation\ValidationException::withMessages([
            'credentials' => "Incorrect username or password. Attempts: {$violator->failed_attempts} / {$maxAttempts}",
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('violator')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        return redirect()->route('violator.showLogin');
    }
    /**
     * Show the change-password form if flagged.
     */
    public function showChangePasswordForm()
    {
        if (! session('must_change_password')) {
            return redirect()->route('violator.dashboard');
        }
        return view('auth.violatorChangePassword');
    }
    /**
     * Handle a change-password submission.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|confirmed|min:8',
        ]);

        $violator = Auth::guard('violator')->user();
        $violator->password = Hash::make($request->password);
        $violator->defaultPassword = null;
        $violator->save();

        Auth::guard('violator')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('violator.showLogin')
                         ->with('status', 'Password changed. Please log in with your new password.');
    }


}
