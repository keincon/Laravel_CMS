<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class E2eSeedCommand extends Command
{
    protected $signature = 'laravelpress:e2e-seed
        {--email=e2e@example.com : Admin email}
        {--password=password : Admin password}';

    protected $description = 'Seed a deterministic admin user for browser/e2e tests';

    public function handle(
        InstallationService $install,
        LaravelPressBootstrapService $bootstrap,
        RolePermissionService $roles,
    ): int {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $install->markInstalled((string) $this->option('email'));
        $bootstrap->seedBuiltins();
        $roles->syncDefaults();

        $email = (string) $this->option('email');
        $password = (string) $this->option('password');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'E2E Admin',
                'username' => 'e2eadmin',
                'password' => Hash::make($password),
            ]
        );
        $user->syncRoles(['Administrator']);

        $this->info("E2E admin ready: {$email} / {$password}");

        return self::SUCCESS;
    }
}
