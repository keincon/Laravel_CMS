<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Invalid email or password.');
        }

        $user = $request->user();
        if ($user?->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('login.two_factor_user_id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));

            return redirect()->route('login.two-factor');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showTwoFactor(): View|RedirectResponse
    {
        if (! session()->has('login.two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verifyTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('login.two_factor_user_id');
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user || ! $twoFactor->verify($user, $data['code'])) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.two_factor_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
