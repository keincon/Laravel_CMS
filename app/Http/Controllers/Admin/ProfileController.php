<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', [
            'user' => $request->user()->load('roles'),
            'pendingTwoFactor' => session('two_factor_setup'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function enableTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $setup = $twoFactor->enable($request->user());

        return back()->with('two_factor_setup', $setup)->with('success', 'Scan the secret in your authenticator, then confirm with a code.');
    }

    public function confirmTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $twoFactor->confirm($request->user(), $data['code'])) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        return back()->with('success', 'Two-factor authentication enabled.');
    }

    public function disableTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        unset($data);
        $twoFactor->disable($request->user());

        return back()->with('success', 'Two-factor authentication disabled.');
    }
}
