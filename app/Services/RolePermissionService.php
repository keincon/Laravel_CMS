<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionService
{
    /**
     * Ensure WordPress-like roles and permissions exist.
     *
     * @return array<string, Role>
     */
    public function syncDefaults(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');
        $defaults = Role::defaultCapabilities();

        foreach (config('cms.permissions', []) as $name) {
            $permission = Permission::findOrCreate($name, $guard);
            $label = Role::capabilityDefinitions()[$name] ?? Str::headline(str_replace('_', ' ', $name));
            if ($permission->description !== $label) {
                $permission->description = $label;
                $permission->save();
            }
        }

        $roles = [];
        foreach (config('cms.roles', []) as $roleName) {
            $role = Role::findOrCreate($roleName, $guard);
            if (blank($role->description)) {
                $role->description = "{$roleName} role";
                $role->save();
            }
            $role->syncPermissions($defaults[$roleName] ?? []);
            $roles[$roleName] = $role;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $roles;
    }
}
