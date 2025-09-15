<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Enforcer;

class EnforcerAuthController extends Controller
{
    //
    public function showLogin()
    {
        return view("auth.enforcerLogin");
    }
    //
    /** Detect if the submitted password is still the default */
    private function isUsingDefaultPassword(Enforcer $user, string $input): bool
    {
        // Support ANY of these (use what you actually have)
        // If you mark them explicitly
        if (($user->mustChangePassword ?? false) || ($user->must_change_password ?? false)) {
            return true;
        }

        // If a default is still recorded, always force change
        if (!is_null($user->defaultPassword) || !is_null($user->defaultPasswordHash)) {
            return true;
        }

        // --- Optional fallback checks (only if you REALLY need them) ---

        // If you sometimes store the *hash* in defaultPassword by mistake,
        // try to detect and verify it as a bcrypt hash:
        $plain = $user->defaultPassword ?? null;
        if (is_string($plain) && strlen($plain) === 60 && str_starts_with($plain, '$2y$')) {
            if (\Illuminate\Support\Facades\Hash::check($input, $plain)) {
                return true;
            }
        }

        // If you intentionally store a separate hash:
        $hash = $user->defaultPasswordHash ?? null;
        if ($hash && \Illuminate\Support\Facades\Hash::check($input, $hash)) {
            return true;
        }

        return false;
    }
    //
    public function login(Request $request)
    {
        $credentials = $request->validate([
            "badge_num" => "required|string",
            "password"  => "required|string",
        ]);

        $maxAttempts   = (int) env('AUTH_MAX_FAILED_LOGINS', 5);
        $baseLockMins  = (int) env('AUTH_LOCKOUT_MINUTES', 15);
        $useExpBackoff = filter_var(env('AUTH_LOCKOUT_EXP_BACKOFF', false), FILTER_VALIDATE_BOOL);
        $maxLockMins   = (int) env('AUTH_LOCKOUT_MAX_MINUTES', 1440);

        $user = Enforcer::withTrashed()->where('badge_num', $credentials['badge_num'])->first();

        if (! $user) {
            return back()->withErrors(['badge_num' => 'Invalid badge number or password.'])->withInput();
        }

        if (method_exists($user, 'trashed') && $user->trashed()) {
            return back()->withErrors(['badge_num' => 'Your account is deactivated. Please contact the Admin or go to Admin Office.'])->withInput();
        }

        if ($user->lockout_until && now()->lt($user->lockout_until)) {
            $remaining = now()->diffInSeconds($user->lockout_until);
            return back()->withErrors(['badge_num' => 'Account temporarily locked. Try again later.'])
                         ->with('lockout_remaining', $remaining)
                         ->withInput();
        }

        // Validate password against stored hash
        if (Hash::check($credentials['password'], $user->password)) {
            // Reset counters
            $user->failed_attempts = 0;
            $user->lockout_until   = null;
            $user->lockouts_count  = 0;
            $user->save();

            // Log them in
            Auth::guard('enforcer')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // If they used the default, force a password change
            if ($this->isUsingDefaultPassword($user, $credentials['password'])) {
                $request->session()->put('force_pwd_change', true);
                return redirect()->route('enforcer.password.edit')
                    ->with('error', 'Default password detected. Please change your password before continuing.');
            }

            return redirect()->intended('/enforcerTicket');
        }

        // Wrong password logic (unchanged)
        $user->failed_attempts = (int) $user->failed_attempts + 1;

        if ($user->failed_attempts >= $maxAttempts) {
            $lockMinutes = $baseLockMins;
            if ($useExpBackoff) {
                $lockMinutes = min($baseLockMins * (2 ** (int) $user->lockouts_count), $maxLockMins);
            }

            $user->lockout_until   = now()->addMinutes($lockMinutes);
            $user->lockouts_count  = (int) $user->lockouts_count + 1;
            $user->failed_attempts = 0;
            $user->save();

            return back()
                ->withErrors(['badge_num' => "Too many attempts. Account locked for {$lockMinutes} minute(s)."])
                ->with('lockout_remaining', now()->diffInSeconds($user->lockout_until));
        }

        $user->save();

        return back()->withErrors([
            'badge_num' => "Invalid badge number or password. Attempts: {$user->failed_attempts} / {$maxAttempts}",
        ])->withInput();
    }
    public function logout(Request $request)
    {
        Auth::guard('enforcer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('enforcer.showLogin');
    }
     public function showChangePassword()
    {

        return view('auth.enforcerChangePassword');
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ]);

        $enforcer = Enforcer::find(Auth::guard('enforcer')->id());

        // 1) Save new password
        $enforcer->password = Hash::make($request->password);

        // ✅ Clear all “default” markers (camelCase first; snake_case fallback if present)
        if (isset($enforcer->defaultPassword))     $enforcer->defaultPassword     = null;
        if (isset($enforcer->defaultPasswordHash)) $enforcer->defaultPasswordHash = null;
        if (isset($enforcer->mustChangePassword))  $enforcer->mustChangePassword  = false;

        if (isset($enforcer->default_password))       $enforcer->default_password = null;
        if (isset($enforcer->default_password_hash))  $enforcer->default_password_hash = null;
        if (isset($enforcer->must_change_password))   $enforcer->must_change_password = false;

        if (property_exists($enforcer, 'password_changed_at') || isset($enforcer->password_changed_at)) {
            $enforcer->password_changed_at = now();
        }

        $enforcer->save();

        // 3) Clear session flag and log out
        $request->session()->forget('force_pwd_change');
        Auth::guard('enforcer')->logout();

        return redirect()
            ->route('enforcer.showLogin')
            ->with('status', 'Password successfully changed. Please log in again.');
    }
}
