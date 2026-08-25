<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsSetting;
use App\Models\Installation;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SeoSetting;
use App\Models\Theme;
use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class InstallationService
{
    public function __construct(
        protected SystemRequirementsService $requirements,
        protected UIFrameworkService $uiFramework,
    ) {}

    public function isInstalled(): bool
    {
        $file = config('cms.installed_file');

        if (! is_string($file) || ! File::exists($file)) {
            return false;
        }

        try {
            $payload = json_decode(File::get($file), true, 512, JSON_THROW_ON_ERROR);
            if (empty($payload['installed_at'])) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        // Prefer database confirmation when the installations table exists.
        try {
            if (Schema::hasTable('installations')) {
                return Installation::query()->exists();
            }
        } catch (Throwable) {
            // Database may be unavailable; file marker is enough for routing.
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function requirements(): array
    {
        return $this->requirements->check();
    }

    /**
     * Run the full installation using wizard session data.
     *
     * @param  array{
     *     database: array{host: string, port: string|int, database: string, username: string, password?: string},
     *     website: array{name: string, description?: string, url: string, timezone: string, language: string, date_format: string},
     *     administrator: array{name: string, username: string, email: string, password: string},
     *     appearance: array{ui_framework: string}
     * }  $config
     * @return array{success: bool, message: string, steps?: list<array{key: string, label: string, status: string}>}
     */
    public function install(array $config): array
    {
        $steps = [];

        try {
            $this->assertNotInstalled();
            $this->validateConfig($config);
            $steps[] = $this->step('validate', 'Validating configuration', 'done');

            $db = $config['database'];
            $test = $this->requirements->testDatabaseConnection($db);
            if (! $test['success']) {
                throw new \RuntimeException('database_connection_failed');
            }
            $steps[] = $this->step('database_test', 'Testing database', 'done');

            $this->writeEnvironment($config);
            $this->requirements->configureRuntimeConnection($db);
            $steps[] = $this->step('env', 'Saving configuration', 'done');

            Artisan::call('migrate', ['--force' => true]);
            $steps[] = $this->step('migrate', 'Creating database tables', 'done');

            DB::transaction(function () use ($config, &$steps) {
                $roles = $this->createRoles();
                $steps[] = $this->step('roles', 'Creating roles', 'done');

                $this->createPermissions($roles);
                $steps[] = $this->step('permissions', 'Creating permissions', 'done');

                $admin = $this->createAdministrator($config['administrator'], $roles['Administrator']);
                $steps[] = $this->step('administrator', 'Creating administrator', 'done');

                $pages = $this->createDefaultPages($admin);
                $steps[] = $this->step('pages', 'Creating default pages', 'done');

                $this->createDefaultMenu($pages);
                $steps[] = $this->step('menu', 'Creating default menu', 'done');

                $this->createDefaultCategories();
                $steps[] = $this->step('categories', 'Creating default categories', 'done');

                $this->createDefaultTheme();
                $steps[] = $this->step('theme', 'Creating theme', 'done');

                $this->createDefaultSettings($config, $pages);
                $this->createSeoAndThemeSettings($config);
                app(LayoutBootstrapService::class)->ensureDefaults($pages['home'] ?? null, $pages['blog'] ?? null);
                $steps[] = $this->step('settings', 'Creating default settings', 'done');

                $this->uiFramework->set($config['appearance']['ui_framework'] ?? 'tailwind');
                $steps[] = $this->step('ui', 'Configuring UI framework', 'done');

                $this->markInstalled($admin->email, [
                    'site_name' => $config['website']['name'],
                    'ui_framework' => $config['appearance']['ui_framework'] ?? 'tailwind',
                ]);
                $steps[] = $this->step('finalize', 'Finalizing installation', 'done');
            });

            $this->clearCaches();
            $steps[] = $this->step('cache', 'Clearing caches', 'done');

            return [
                'success' => true,
                'message' => 'Installation completed successfully.',
                'steps' => $steps,
            ];
        } catch (Throwable $e) {
            Log::error('CMS installation failed', [
                'exception' => $e::class,
                'message' => $this->sanitizeErrorMessage($e->getMessage()),
            ]);

            return [
                'success' => false,
                'message' => 'Installation could not be completed. Please check your database configuration and try again.',
                'steps' => $steps,
            ];
        }
    }

    public function markInstalled(?string $installedBy = null, array $meta = []): void
    {
        Installation::query()->create([
            'version' => '1.0.0',
            'installed_at' => now(),
            'installed_by' => $installedBy,
            'meta' => $meta,
        ]);

        CmsSetting::setValue('installed', '1', 'boolean');
        CmsSetting::setValue('installed_at', now()->toIso8601String());

        $path = config('cms.installed_file');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'installed_by' => $installedBy,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Encrypt administrator password for temporary session storage.
     */
    public function encryptPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    public function decryptPassword(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }

    protected function assertNotInstalled(): void
    {
        if ($this->isInstalled()) {
            throw new \RuntimeException('already_installed');
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function validateConfig(array $config): void
    {
        foreach (['database', 'website', 'administrator', 'appearance'] as $key) {
            if (empty($config[$key]) || ! is_array($config[$key])) {
                throw new \InvalidArgumentException("Missing setup configuration: {$key}");
            }
        }

        $password = $config['administrator']['password'] ?? '';
        if (! $this->isStrongPassword($password)) {
            throw new \InvalidArgumentException('Administrator password does not meet strength requirements.');
        }
    }

    public function isStrongPassword(string $password): bool
    {
        if (strlen($password) < 12) {
            return false;
        }

        // Require mixed case, a digit, and a symbol for non-technical but safe defaults.
        return (bool) preg_match('/[a-z]/', $password)
            && (bool) preg_match('/[A-Z]/', $password)
            && (bool) preg_match('/[0-9]/', $password)
            && (bool) preg_match('/[^A-Za-z0-9]/', $password);
    }

    /**
     * @param  array{
     *     database: array{host: string, port: string|int, database: string, username: string, password?: string},
     *     website: array{name: string, description?: string, url: string, timezone: string, language: string, date_format: string}
     * }  $config
     */
    protected function writeEnvironment(array $config): void
    {
        $db = $config['database'];
        $website = $config['website'];

        $replacements = [
            'APP_NAME' => '"'.addslashes($website['name']).'"',
            'APP_URL' => $website['url'],
            'APP_TIMEZONE' => $website['timezone'] ?? 'UTC',
            'APP_LOCALE' => $website['language'] ?? 'en',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $db['host'],
            'DB_PORT' => (string) $db['port'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'] ?? '',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ];

        $envPath = base_path('.env');
        $content = File::exists($envPath) ? File::get($envPath) : File::get(base_path('.env.example'));

        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $line = "{$key}={$value}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        // Ensure commented sqlite leftovers do not confuse operators.
        $content = preg_replace('/^#\s*DB_HOST=.*/m', '', $content) ?? $content;

        File::put($envPath, $content);

        if (! empty($website['timezone'])) {
            config(['app.timezone' => $website['timezone']]);
            date_default_timezone_set($website['timezone']);
        }

        if (! empty($website['url'])) {
            config(['app.url' => $website['url']]);
        }

        if (! empty($website['name'])) {
            config(['app.name' => $website['name']]);
        }
    }

    /**
     * @return array<string, Role>
     */
    protected function createRoles(): array
    {
        $roles = [];

        foreach (config('cms.roles', []) as $name) {
            $roles[$name] = Role::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => "{$name} role"]
            );
        }

        return $roles;
    }

    /**
     * @param  array<string, Role>  $roles
     */
    protected function createPermissions(array $roles): void
    {
        $permissionModels = [];

        foreach (config('cms.permissions', []) as $slug) {
            $permissionModels[] = Permission::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => Str::headline(str_replace('_', ' ', $slug)),
                    'description' => Str::headline(str_replace('_', ' ', $slug)),
                ]
            );
        }

        if (isset($roles['Administrator'])) {
            $roles['Administrator']->permissions()->sync(
                collect($permissionModels)->pluck('id')->all()
            );
        }

        if (isset($roles['Editor'])) {
            $roles['Editor']->permissions()->sync(
                collect($permissionModels)
                    ->filter(fn (Permission $p) => ! in_array($p->slug, ['manage_settings', 'manage_users', 'manage_roles'], true))
                    ->pluck('id')
                    ->all()
            );
        }
    }

    /**
     * @param  array{name: string, username: string, email: string, password: string}  $admin
     */
    protected function createAdministrator(array $admin, Role $role): User
    {
        $user = User::query()->create([
            'name' => $admin['name'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'password' => Hash::make($admin['password']),
            'email_verified_at' => now(),
        ]);

        $user->roles()->attach($role->id);

        return $user;
    }

    /**
     * @return array<string, Page>
     */
    protected function createDefaultPages(User $author): array
    {
        $definitions = [
            'home' => ['title' => 'Home', 'content' => '<p>Welcome to your new website.</p>'],
            'blog' => ['title' => 'Blog', 'content' => '<p>Blog posts will appear here.</p>'],
            'about' => ['title' => 'About', 'content' => '<p>About us.</p>'],
            'contact' => ['title' => 'Contact', 'content' => '<p>Get in touch.</p>'],
        ];

        $pages = [];

        foreach ($definitions as $slug => $data) {
            $pages[$slug] = Page::query()->create([
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'status' => 'publish',
                'author_id' => $author->id,
                'published_at' => now(),
            ]);
        }

        return $pages;
    }

    /**
     * @param  array<string, Page>  $pages
     */
    protected function createDefaultMenu(array $pages): void
    {
        $menu = Menu::query()->create([
            'name' => 'Primary',
            'slug' => 'primary',
            'location' => 'primary',
        ]);

        $order = 0;
        foreach (['home', 'about', 'blog', 'contact'] as $slug) {
            if (! isset($pages[$slug])) {
                continue;
            }

            MenuItem::query()->create([
                'menu_id' => $menu->id,
                'title' => $pages[$slug]->title,
                'page_id' => $pages[$slug]->id,
                'url' => '/'.$pages[$slug]->slug,
                'sort_order' => $order++,
            ]);
        }
    }

    protected function createDefaultCategories(): void
    {
        Category::query()->firstOrCreate(
            ['slug' => 'uncategorized'],
            [
                'name' => 'Uncategorized',
                'description' => 'Default category',
            ]
        );
    }

    protected function createDefaultTheme(): void
    {
        Theme::query()->create([
            'name' => 'Default',
            'slug' => 'default',
            'version' => '1.0.0',
            'is_active' => true,
            'settings' => [
                'layout' => 'default',
            ],
        ]);
    }

    /**
     * @param  array{
     *     website: array{name: string, description?: string, url: string, timezone: string, language: string, date_format: string},
     *     appearance: array{ui_framework: string}
     * }  $config
     * @param  array<string, Page>  $pages
     */
    protected function createDefaultSettings(array $config, array $pages): void
    {
        $website = $config['website'];

        $settings = [
            'site_name' => [$website['name'], 'string'],
            'site_description' => [$website['description'] ?? '', 'string'],
            'site_url' => [$website['url'], 'string'],
            'timezone' => [$website['timezone'], 'string'],
            'language' => [$website['language'], 'string'],
            'date_format' => [$website['date_format'], 'string'],
            'ui_framework' => [$config['appearance']['ui_framework'] ?? 'tailwind', 'string'],
            'homepage' => [(string) ($pages['home']->id ?? ''), 'string'],
            'posts_page' => [(string) ($pages['blog']->id ?? ''), 'string'],
        ];

        foreach ($settings as $key => [$value, $type]) {
            CmsSetting::setValue($key, $value, $type);
        }
    }

    /**
     * @param  array{
     *     website: array{name: string, description?: string, url: string, language: string},
     *     appearance: array{ui_framework: string}
     * }  $config
     */
    protected function createSeoAndThemeSettings(array $config): void
    {
        $website = $config['website'];

        SeoSetting::query()->create([
            'seo_title' => $website['name'],
            'meta_description' => $website['description'] ?? '',
            'canonical_url' => $website['url'],
            'robots' => 'index, follow',
            'og_title' => $website['name'],
            'og_description' => $website['description'] ?? '',
            'og_type' => 'website',
            'og_site_name' => $website['name'],
            'og_locale' => match ($website['language'] ?? 'en') {
                'ja' => 'ja_JP',
                'my' => 'my_MM',
                default => 'en_US',
            },
            'twitter_card' => 'summary_large_image',
            'sitemap_enabled' => true,
            'permalink_structure' => 'blog_slug',
            'organization_name' => $website['name'],
        ]);

        ThemeSetting::query()->create(ThemeSetting::defaults());
    }

    protected function clearCaches(): void
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
        } catch (Throwable $e) {
            Log::warning('Cache clear during install had issues', [
                'message' => $this->sanitizeErrorMessage($e->getMessage()),
            ]);
        }
    }

    /**
     * @return array{key: string, label: string, status: string}
     */
    protected function step(string $key, string $label, string $status): array
    {
        return compact('key', 'label', 'status');
    }

    protected function sanitizeErrorMessage(string $message): string
    {
        $message = preg_replace('/password[=:]\S+/i', 'password=[redacted]', $message) ?? $message;
        $message = preg_replace('/PWD=\S+/i', 'PWD=[redacted]', $message) ?? $message;
        $message = preg_replace('/APP_KEY=\S+/i', 'APP_KEY=[redacted]', $message) ?? $message;

        return $message;
    }
}
