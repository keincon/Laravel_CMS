<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setup\StoreAdministratorRequest;
use App\Http\Requests\Setup\StoreAppearanceRequest;
use App\Http\Requests\Setup\StoreDatabaseRequest;
use App\Http\Requests\Setup\StoreWebsiteRequest;
use App\Services\InstallationService;
use App\Services\SystemRequirementsService;
use App\Services\UIFrameworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Throwable;

class SetupController extends Controller
{
    public function __construct(
        protected InstallationService $installation,
        protected SystemRequirementsService $requirements,
        protected UIFrameworkService $ui,
    ) {}

    public function welcome(): View
    {
        return view('setup.welcome', [
            'cmsName' => config('cms.name'),
            'currentStep' => 1,
        ]);
    }

    public function requirements(): View
    {
        return view('setup.requirements', [
            'cmsName' => config('cms.name'),
            'currentStep' => 2,
            'checks' => $this->requirements->check(),
        ]);
    }

    public function database(): View
    {
        $this->ensureRequirementsPassed();

        $saved = Session::get('setup.database', []);
        $options = $this->requirements->availableDriverOptions();
        $type = $saved['type'] ?? null;
        if (! is_string($type) || $type === '' || ! array_key_exists($type, $options)) {
            if (array_key_exists('pgsql', $options)) {
                $type = 'pgsql';
            } else {
                $first = array_key_first($options);
                if (is_string($first) && $first !== '') {
                    $type = $first;
                } else {
                    $type = 'pgsql';
                }
            }
        }
        $defaults = $this->requirements->supportedDrivers()[$type] ?? ['default_port' => 5432, 'default_host' => '127.0.0.1'];

        return view('setup.database', [
            'cmsName' => config('cms.name'),
            'currentStep' => 3,
            'driverOptions' => $options,
            'driverMeta' => collect($this->requirements->supportedDrivers())
                ->map(fn (array $meta, string $key) => [
                    'label' => $meta['label'],
                    'default_port' => (int) $meta['default_port'],
                    'default_host' => (string) $meta['default_host'],
                    'available' => $this->requirements->driverAvailable($key),
                ])
                ->all(),
            'database' => [
                'type' => $type,
                'host' => $saved['host'] ?? env('DB_HOST', $defaults['default_host'] ?? '127.0.0.1'),
                'port' => $saved['port'] ?? env('DB_PORT', (string) ($defaults['default_port'] ?? 5432)),
                'database' => $saved['database'] ?? env('DB_DATABASE', 'cms'),
                'username' => $saved['username'] ?? env('DB_USERNAME', 'cms'),
                'password' => '',
                // Prefill for Docker compose (password field is blank by default otherwise).
                'password_plain' => env('DB_PASSWORD', ''),
                'tested' => (bool) Session::get('setup.database_tested', false),
            ],
        ]);
    }

    public function testDatabase(StoreDatabaseRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $payload = [
            'type' => $data['type'],
            'host' => $data['host'],
            'port' => $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => $data['password'] ?? '',
        ];

        $result = $this->requirements->testDatabaseConnection($payload);

        if ($result['success']) {
            Session::put('setup.database', [
                'type' => $data['type'],
                'host' => $data['host'],
                'port' => (string) $data['port'],
                'database' => $data['database'],
                'username' => $data['username'],
                'password' => $this->installation->encryptPassword($data['password'] ?? ''),
            ]);
            Session::put('setup.database_tested', true);
        } else {
            Session::forget('setup.database_tested');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        return $result['success']
            ? back()->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function storeDatabase(StoreDatabaseRequest $request): RedirectResponse
    {
        if (! Session::get('setup.database_tested')) {
            return back()->withInput()->with('error', 'Please test the database connection before continuing.');
        }

        $data = $request->validated();
        $saved = Session::get('setup.database', []);

        // Require re-test if connection settings changed after the last successful test.
        if (
            ($saved['type'] ?? null) !== $data['type']
            || ($saved['host'] ?? null) !== $data['host']
            || (string) ($saved['port'] ?? '') !== (string) $data['port']
            || ($saved['database'] ?? null) !== $data['database']
            || ($saved['username'] ?? null) !== $data['username']
        ) {
            Session::forget('setup.database_tested');

            return back()->withInput()->with('error', 'Database settings changed. Please test the connection again.');
        }

        Session::put('setup.database', [
            'type' => $data['type'],
            'host' => $data['host'],
            'port' => (string) $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => $saved['password'] ?? $this->installation->encryptPassword($data['password'] ?? ''),
        ]);

        return redirect()->route('setup.website');
    }

    public function website(): View
    {
        $this->ensureDatabaseConfigured();

        $saved = Session::get('setup.website', []);

        return view('setup.website', [
            'cmsName' => config('cms.name'),
            'currentStep' => 4,
            'website' => [
                'name' => $saved['name'] ?? 'My Website',
                'description' => $saved['description'] ?? 'My awesome website',
                'url' => $saved['url'] ?? url('/'),
                'timezone' => $saved['timezone'] ?? 'Asia/Tokyo',
                'language' => $saved['language'] ?? 'en',
                'date_format' => $saved['date_format'] ?? 'Y-m-d',
            ],
            'languages' => config('cms.languages'),
            'dateFormats' => config('cms.date_formats'),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function storeWebsite(StoreWebsiteRequest $request): RedirectResponse
    {
        Session::put('setup.website', $request->validated());

        return redirect()->route('setup.administrator');
    }

    public function administrator(): View
    {
        $this->ensureWebsiteConfigured();

        $saved = Session::get('setup.administrator', []);

        return view('setup.administrator', [
            'cmsName' => config('cms.name'),
            'currentStep' => 5,
            'administrator' => [
                'name' => $saved['name'] ?? '',
                'username' => $saved['username'] ?? 'admin',
                'email' => $saved['email'] ?? '',
            ],
        ]);
    }

    public function storeAdministrator(StoreAdministratorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Session::put('setup.administrator', [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $this->installation->encryptPassword($data['password']),
        ]);

        return redirect()->route('setup.appearance');
    }

    public function appearance(): View
    {
        $this->ensureAdministratorConfigured();

        $saved = Session::get('setup.appearance', []);

        return view('setup.appearance', [
            'cmsName' => config('cms.name'),
            'currentStep' => 6,
            'selected' => $saved['ui_framework'] ?? config('cms.default_ui_framework'),
            'frameworks' => $this->ui->available(),
        ]);
    }

    public function storeAppearance(StoreAppearanceRequest $request): RedirectResponse
    {
        Session::put('setup.appearance', $request->validated());

        return redirect()->route('setup.install.show');
    }

    public function showInstall(): View|RedirectResponse
    {
        if (! $this->readyToInstall()) {
            return redirect()->route('setup.welcome');
        }

        return view('setup.installing', [
            'cmsName' => config('cms.name'),
            'currentStep' => 7,
            'summary' => [
                'website' => Session::get('setup.website.name'),
                'url' => Session::get('setup.website.url'),
                'admin' => Session::get('setup.administrator.email'),
                'framework' => Session::get('setup.appearance.ui_framework'),
            ],
        ]);
    }

    public function install(Request $request): JsonResponse|RedirectResponse|View
    {
        if (! $this->readyToInstall()) {
            return redirect()->route('setup.welcome')
                ->with('error', 'Please complete all setup steps before installing.');
        }

        try {
            $config = $this->buildInstallConfig();
        } catch (Throwable $e) {
            report($e);
            $hint = str_contains(strtolower($e->getMessage()), 'decrypt') || str_contains($e::class, 'Decrypt')
                ? 'Saved passwords could not be read. Go back to Database and Administrator, re-enter passwords, then install again.'
                : 'Setup data is incomplete or invalid. Please restart the wizard.';

            return $this->installFailedResponse($request, [], $hint);
        }

        $result = $this->installation->install($config);

        if (! $result['success']) {
            return $this->installFailedResponse(
                $request,
                $result['steps'] ?? [],
                $result['message'] ?? null
            );
        }

        Session::forget([
            'setup.database',
            'setup.database_tested',
            'setup.website',
            'setup.administrator',
            'setup.appearance',
        ]);

        Session::put('setup.just_completed', true);
        Session::put('setup.complete_url', $config['website']['url']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'redirect' => route('setup.complete'),
                'steps' => $result['steps'],
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('setup.complete');
    }

    public function complete(): View|RedirectResponse
    {
        // Allow the completion page only right after install (session flag),
        // middleware will 404 once installed and this flag is gone on later visits —
        // but we need an exception: after install, middleware blocks /setup.
        // So complete must be reachable while installed briefly.
        // Handled by excluding complete from 404 in middleware via session OR
        // we show complete BEFORE marking... Actually we mark installed first.
        // Fix: CheckInstallation should allow setup/complete when session flag is set.

        $url = rtrim((string) Session::get('setup.complete_url', config('app.url')), '/');

        return view('setup.complete', [
            'cmsName' => config('cms.name'),
            'currentStep' => 8,
            'websiteUrl' => $url ?: url('/'),
            'adminUrl' => ($url ?: url('/')).'/admin',
        ]);
    }

    public function failed(): View
    {
        return view('setup.failed', [
            'cmsName' => config('cms.name'),
            'currentStep' => 7,
        ]);
    }

    protected function installFailedResponse(Request $request, array $steps = [], ?string $message = null): JsonResponse|RedirectResponse
    {
        $message ??= 'Installation could not be completed. Please check your settings and try again.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'steps' => $steps,
                'redirect' => route('setup.failed'),
            ], 422);
        }

        return redirect()->route('setup.failed')->with('error', $message);
    }

    /**
     * @return array{
     *     database: array{host: string, port: string, database: string, username: string, password: string},
     *     website: array<string, mixed>,
     *     administrator: array{name: string, username: string, email: string, password: string},
     *     appearance: array{ui_framework: string}
     * }
     */
    protected function buildInstallConfig(): array
    {
        $db = Session::get('setup.database');
        $admin = Session::get('setup.administrator');

        return [
            'database' => [
                'type' => $db['type'] ?? 'pgsql',
                'host' => $db['host'],
                'port' => $db['port'],
                'database' => $db['database'],
                'username' => $db['username'],
                'password' => $this->installation->decryptPassword($db['password'] ?? ''),
            ],
            'website' => Session::get('setup.website'),
            'administrator' => [
                'name' => $admin['name'],
                'username' => $admin['username'],
                'email' => $admin['email'],
                'password' => $this->installation->decryptPassword($admin['password']),
            ],
            'appearance' => Session::get('setup.appearance'),
        ];
    }

    protected function readyToInstall(): bool
    {
        return Session::has('setup.database')
            && Session::get('setup.database_tested')
            && Session::has('setup.website')
            && Session::has('setup.administrator')
            && Session::has('setup.appearance');
    }

    protected function ensureRequirementsPassed(): void
    {
        if (! $this->requirements->check()['passed']) {
            throw new HttpResponseException(redirect()->route('setup.requirements'));
        }
    }

    protected function ensureDatabaseConfigured(): void
    {
        $this->ensureRequirementsPassed();

        if (! Session::get('setup.database_tested') || ! Session::has('setup.database')) {
            throw new HttpResponseException(redirect()->route('setup.database'));
        }
    }

    protected function ensureWebsiteConfigured(): void
    {
        $this->ensureDatabaseConfigured();

        if (! Session::has('setup.website')) {
            throw new HttpResponseException(redirect()->route('setup.website'));
        }
    }

    protected function ensureAdministratorConfigured(): void
    {
        $this->ensureWebsiteConfigured();

        if (! Session::has('setup.administrator')) {
            throw new HttpResponseException(redirect()->route('setup.administrator'));
        }
    }
}
