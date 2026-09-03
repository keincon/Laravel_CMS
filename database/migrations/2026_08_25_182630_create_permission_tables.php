<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $teams = config('permission.teams');

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        $legacyRoles = [];
        $legacyPermissions = [];
        $legacyRoleUser = [];
        $legacyPermissionRole = [];
        $isLegacy = Schema::hasTable('roles') && Schema::hasColumn('roles', 'slug');

        if ($isLegacy) {
            $legacyRoles = DB::table('roles')->get()->all();
            $legacyPermissions = DB::table('permissions')->get()->all();
            if (Schema::hasTable('role_user')) {
                $legacyRoleUser = DB::table('role_user')->get()->all();
            }
            if (Schema::hasTable('permission_role')) {
                $legacyPermissionRole = DB::table('permission_role')->get()->all();
            }

            Schema::dropIfExists('permission_role');
            Schema::dropIfExists('role_user');
            Schema::dropIfExists('permissions');
            Schema::dropIfExists('roles');
        }

        if (! Schema::hasTable($tableNames['permissions'])) {
            Schema::create($tableNames['permissions'], static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->string('description')->nullable();
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        } elseif (! Schema::hasColumn($tableNames['permissions'], 'description')) {
            Schema::table($tableNames['permissions'], static function (Blueprint $table) {
                $table->string('description')->nullable();
            });
        }

        if (! Schema::hasTable($tableNames['roles'])) {
            Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
                $table->id();
                if ($teams || config('permission.testing')) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                    $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
                }
                $table->string('name');
                $table->string('guard_name');
                $table->text('description')->nullable();
                $table->timestamps();
                if ($teams || config('permission.testing')) {
                    $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        } elseif (! Schema::hasColumn($tableNames['roles'], 'description')) {
            Schema::table($tableNames['roles'], static function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasTable($tableNames['model_has_permissions'])) {
            Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
                $table->unsignedBigInteger($pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->cascadeOnDelete();
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                    $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
                } else {
                    $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
                }
            });
        }

        if (! Schema::hasTable($tableNames['model_has_roles'])) {
            Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
                $table->unsignedBigInteger($pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->cascadeOnDelete();
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                    $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
                } else {
                    $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
                }
            });
        }

        if (! Schema::hasTable($tableNames['role_has_permissions'])) {
            Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);
                $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->cascadeOnDelete();
                $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->cascadeOnDelete();
                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        if ($isLegacy) {
            $roleIdMap = [];
            foreach ($legacyRoles as $role) {
                $newId = DB::table($tableNames['roles'])->insertGetId([
                    'name' => $role->name,
                    'guard_name' => 'web',
                    'description' => $role->description ?? null,
                    'created_at' => $role->created_at ?? now(),
                    'updated_at' => $role->updated_at ?? now(),
                ]);
                $roleIdMap[$role->id] = $newId;
            }

            $permissionIdMap = [];
            foreach ($legacyPermissions as $permission) {
                $name = $permission->slug ?: $permission->name;
                $newId = DB::table($tableNames['permissions'])->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'description' => $permission->description ?? $permission->name,
                    'created_at' => $permission->created_at ?? now(),
                    'updated_at' => $permission->updated_at ?? now(),
                ]);
                $permissionIdMap[$permission->id] = $newId;
            }

            $userModel = config('auth.providers.users.model', \App\Models\User::class);
            foreach ($legacyRoleUser as $row) {
                if (! isset($roleIdMap[$row->role_id])) {
                    continue;
                }
                DB::table($tableNames['model_has_roles'])->insertOrIgnore([
                    $pivotRole => $roleIdMap[$row->role_id],
                    'model_type' => $userModel,
                    $columnNames['model_morph_key'] => $row->user_id,
                ]);
            }

            foreach ($legacyPermissionRole as $row) {
                if (! isset($roleIdMap[$row->role_id], $permissionIdMap[$row->permission_id])) {
                    continue;
                }
                DB::table($tableNames['role_has_permissions'])->insertOrIgnore([
                    $pivotPermission => $permissionIdMap[$row->permission_id],
                    $pivotRole => $roleIdMap[$row->role_id],
                ]);
            }
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        throw_if(empty($tableNames), 'Error: config/permission.php not found.');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
