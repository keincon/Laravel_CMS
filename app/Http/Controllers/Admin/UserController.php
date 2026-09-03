<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = trim((string) $request->query('role', ''));

        $roles = Role::query()->orderBy('name')->get();

        $users = User::query()
            ->with('roles')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('username', 'like', '%'.$q.'%');
                });
            })
            ->when($role !== '', fn ($query) => $query->role($role))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roleCounts = ['all' => User::query()->count()];
        foreach ($roles as $r) {
            $roleCounts[$r->name] = User::role($r->name)->count();
        }

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'q' => $q,
            'roleFilter' => $role,
            'roleCounts' => $roleCounts,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        if ($user->isAdministrator() && $data['role'] !== 'Administrator') {
            $adminCount = User::role('Administrator')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'You cannot demote the last Administrator.');
            }
        }

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isAdministrator() && User::role('Administrator')->count() <= 1) {
            return back()->with('error', 'You cannot delete the last Administrator.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:delete,change_role'],
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['integer', 'exists:users,id'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
        ]);

        $ids = collect($data['users'])->map(fn ($id) => (int) $id)->unique()->values();
        $ids = $ids->reject(fn ($id) => $id === auth()->id());

        if ($ids->isEmpty()) {
            return back()->with('error', 'No eligible users selected.');
        }

        if ($data['action'] === 'change_role') {
            if (empty($data['role'])) {
                return back()->with('error', 'Choose a role for the bulk change.');
            }

            $users = User::query()->whereIn('id', $ids)->get();
            foreach ($users as $user) {
                if ($user->isAdministrator() && $data['role'] !== 'Administrator' && User::role('Administrator')->count() <= 1) {
                    continue;
                }
                $user->syncRoles([$data['role']]);
            }

            return back()->with('success', 'Roles updated for selected users.');
        }

        $users = User::query()->whereIn('id', $ids)->get();
        foreach ($users as $user) {
            if ($user->isAdministrator() && User::role('Administrator')->count() <= 1) {
                continue;
            }
            $user->delete();
        }

        return back()->with('success', 'Selected users deleted.');
    }
}
