<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsSetting;
use App\Models\Installation;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Role;
use App\Services\RolePermissionService;
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
        // If the file marker is valid but the DB row is missing (volume restore /
        // partial install), treat as installed and heal the row when possible.
        try {
            if (Schema::hasTable('installations')) {
                if (Installation::query()->exists()) {
                    return true;
                }

                try {
                    Installation::query()->create([
                        'version' => (string) ($payload['version'] ?? '1.0.0'),
                        'installed_at' => now(),
                        'installed_by' => $payload['installed_by'] ?? null,
                        'meta' => ['healed_from_file' => true],
                    ]);
                } catch (Throwable) {
                    // Still trust the file marker for routing.
                }

                return true;
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
                $roles = app(RolePermissionService::class)->syncDefaults();
                $steps[] = $this->step('roles', 'Creating roles', 'done');
                $steps[] = $this->step('permissions', 'Creating permissions', 'done');

                app(LaravelPressBootstrapService::class)->seedBuiltins();
                $steps[] = $this->step('content_types', 'Seeding content types & taxonomies', 'done');

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
                app(LayoutBootstrapService::class)->ensureDefaults($pages['home'] ?? null, null);
                app(DynamicPageService::class)->ensureDefaults();
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
            $safe = $this->sanitizeErrorMessage($e->getMessage());

            Log::error('CMS installation failed', [
                'exception' => $e::class,
                'message' => $safe,
            ]);

            return [
                'success' => false,
                'message' => $this->friendlyInstallError($e),
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
            'DB_CONNECTION' => $db['type'] ?? 'pgsql',
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

        // Prefer atomic replace so watchers see one consistent write, and skip
        // rewriting when Docker already injects the same DB settings (compose).
        $skipWrite = (bool) env('CMS_SKIP_ENV_WRITE', false)
            || (
                getenv('DB_HOST')
                && (string) getenv('DB_HOST') === (string) $db['host']
                && (string) getenv('DB_DATABASE') === (string) $db['database']
                && (string) getenv('DB_USERNAME') === (string) $db['username']
            );

        if (! $skipWrite) {
            $tmp = $envPath.'.install-tmp';
            File::put($tmp, $content);
            File::move($tmp, $envPath);
        }

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

        // Always apply DB config for this request even if .env write was skipped.
        config([
            'database.default' => $db['type'] ?? 'pgsql',
            'database.connections.'.($db['type'] ?? 'pgsql').'.host' => $db['host'],
            'database.connections.'.($db['type'] ?? 'pgsql').'.port' => $db['port'],
            'database.connections.'.($db['type'] ?? 'pgsql').'.database' => $db['database'],
            'database.connections.'.($db['type'] ?? 'pgsql').'.username' => $db['username'],
            'database.connections.'.($db['type'] ?? 'pgsql').'.password' => $db['password'] ?? '',
        ]);
    }

    /**
     * @return array<string, Role>
     */
    protected function createRoles(): array
    {
        return app(RolePermissionService::class)->syncDefaults();
    }

    /**
     * @param  array<string, Role>  $roles
     */
    protected function createPermissions(array $roles): void
    {
        // Permissions are synced with roles in RolePermissionService::syncDefaults().
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

        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<string, Page>
     */
    protected function createDefaultPages(User $author): array
    {
        // Static pages only — never seed reserved dynamic slugs like "blog".
        $definitions = [
            'home' => ['title' => 'Home', 'content' => '<p>Welcome to your new website.</p>'],
            'about' => ['title' => 'About', 'content' => '<p>About us.</p>'],
            'contact' => ['title' => 'Contact', 'content' => '<p>Get in touch.</p>'],
            'privacy-policy' => ['title' => 'Privacy Policy', 'content' => '<p>Your privacy policy goes here.</p>'],
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

        $items = [
            ['title' => 'Home', 'url' => '/', 'page_id' => $pages['home']->id ?? null],
            ['title' => 'About', 'url' => '/about', 'page_id' => $pages['about']->id ?? null],
            ['title' => 'Blog', 'url' => '/blog', 'page_id' => null], // Dynamic Blog Archive
            ['title' => 'Contact', 'url' => '/contact', 'page_id' => $pages['contact']->id ?? null],
        ];

        foreach ($items as $order => $item) {
            MenuItem::query()->create([
                'menu_id' => $menu->id,
                'title' => $item['title'],
                'page_id' => $item['page_id'],
                'url' => $item['url'],
                'sort_order' => $order,
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
        $manager = app(\App\Services\Themes\ThemeManager::class);
        $manager->syncDiskThemesToDatabase();
        if (! Theme::query()->where('is_active', true)->exists()) {
            $manager->activate('default', applyColors: false);
        }
        // Ensure downloadable ZIP packs exist for bundled themes.
        try {
            app(\App\Services\Themes\ThemePackageService::class)->buildBundledPacksFromDisk();
        } catch (Throwable) {
            // Zip extension optional during install; packs can be rebuilt from admin.
        }
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
            'posts_page' => ['', 'string'],
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


    protected function friendlyInstallError(Throwable $e): string
    {
        $message = $e->getMessage();

        if ($message === 'already_installed') {
            return 'This CMS is already installed.';
        }
        if ($message === 'database_connection_failed') {
            return 'Could not connect with the saved database settings. Go back to Database and use host "postgres" (not 127.0.0.1), user/password "cms" / "cms_secret".';
        }
        if (str_contains($message, 'Administrator password')) {
            return 'Administrator password must be at least 12 characters and include upper, lower, number, and a symbol (example: MySite2026!). Go back to Administrator and set a stronger password.';
        }
        if (str_contains($message, 'Missing setup configuration')) {
            return 'Setup is incomplete. Please restart the wizard from Welcome.';
        }
        if (str_contains($message, 'Connection refused') || str_contains($message, 'could not translate host name')) {
            return 'Database host is unreachable. In Docker use host "postgres".';
        }
        if (stripos($message, 'authentication failed') !== false || stripos($message, 'no password supplied') !== false) {
            return 'Database username or password is incorrect. Docker defaults: cms / cms_secret.';
        }

        return 'Installation could not be completed: '.$this->sanitizeErrorMessage($message);
    }

    protected function sanitizeErrorMessage(string $message): string
    {
        $message = preg_replace('/password[=:]\S+/i', 'password=[redacted]', $message) ?? $message;
        $message = preg_replace('/PWD=\S+/i', 'PWD=[redacted]', $message) ?? $message;
        $message = preg_replace('/APP_KEY=\S+/i', 'APP_KEY=[redacted]', $message) ?? $message;

        return $message;
    }
}
