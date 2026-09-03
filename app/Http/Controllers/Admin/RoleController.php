<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::query()->with('permissions')->withCount('users')->orderBy('name')->get();
        $selectedName = (string) $request->query('role', $roles->first()?->name ?? 'Administrator');
        $selected = $roles->firstWhere('name', $selectedName) ?? $roles->first();

        return view('admin.users.roles', [
            'roles' => $roles,
            'selected' => $selected,
            'capabilities' => Role::capabilityLabels(),
            'permissionNames' => Permission::query()->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'Administrator') {
            return back()->with('error', 'Administrator always has all capabilities.');
        }

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role->description = $data['description'] ?? $role->description;
        $role->save();
        $role->syncPermissions($data['permissions'] ?? []);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.users.roles', ['role' => $role->name])
            ->with('success', "Capabilities saved for {$role->name}.");
    }
}
