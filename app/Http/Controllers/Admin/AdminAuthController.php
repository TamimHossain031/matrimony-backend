<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_e164';
        $loginValue = $field === 'email' ? $login : (Phone::normalizeBd($login) ?? $login);

        if (! Auth::attempt([$field => $loginValue, 'password' => $credentials['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();

        if ($user->status === AccountStatus::Suspended) {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages([
                'login' => ['This account has been suspended.'],
            ]);
        }

        if ($user->status === AccountStatus::Deleted) {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages([
                'login' => ['This account is no longer available.'],
            ]);
        }

        if (! $user->isStaff()) {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages([
                'login' => ['Access denied. Administrative privileges are required.'],
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_active_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been logged out.');
    }
}
