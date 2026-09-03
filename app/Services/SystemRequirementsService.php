<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class SystemRequirementsService
{
    /**
     * @return array{
     *     passed: bool,
     *     php: array{passed: bool, current: string, required: string, label: string},
     *     extensions: list<array{name: string, passed: bool, label: string}>,
     *     permissions: list<array{path: string, passed: bool, label: string}>,
     *     database: array{passed: bool, label: string, available: bool, drivers: array<string, array{available: bool, label: string}>}
     * }
     */
    public function check(): array
    {
        $php = $this->checkPhp();
        $extensions = $this->checkExtensions();
        $permissions = $this->checkPermissions();
        $database = $this->checkDatabaseDriver();

        $passed = $php['passed']
            && collect($extensions)->every(fn (array $e) => $e['passed'])
            && collect($permissions)->every(fn (array $p) => $p['passed'])
            && $database['passed'];

        return [
            'passed' => $passed,
            'php' => $php,
            'extensions' => $extensions,
            'permissions' => $permissions,
            'database' => $database,
        ];
    }

    /**
     * @return array{passed: bool, current: string, required: string, label: string}
     */
    public function checkPhp(): array
    {
        $required = config('cms.min_php', '8.3.0');
        $current = PHP_VERSION;
        $passed = version_compare($current, $required, '>=');

        return [
            'passed' => $passed,
            'current' => $current,
            'required' => $required,
            'label' => $passed
                ? "PHP {$current}"
                : 'PHP version is too old (requires '.$required.'+)',
        ];
    }

    /**
     * @return list<array{name: string, passed: bool, label: string}>
     */
    public function checkExtensions(): array
    {
        $labels = [
            'pdo' => 'PDO',
            'pdo_pgsql' => 'PDO PostgreSQL',
            'pdo_mysql' => 'PDO MySQL',
            'mbstring' => 'Mbstring',
            'openssl' => 'OpenSSL',
            'tokenizer' => 'Tokenizer',
            'xml' => 'XML',
            'ctype' => 'Ctype',
            'json' => 'JSON',
            'fileinfo' => 'Fileinfo',
            'bcmath' => 'BCMath',
        ];

        $results = [];

        foreach (config('cms.extensions', []) as $extension) {
            $passed = extension_loaded($extension);
            $name = $labels[$extension] ?? $extension;

            $results[] = [
                'name' => $extension,
                'passed' => $passed,
                'label' => $passed ? $name : "{$name} is missing",
            ];
        }

        return $results;
    }

    /**
     * @return list<array{path: string, passed: bool, label: string}>
     */
    public function checkPermissions(): array
    {
        $results = [];

        foreach (config('cms.writable_paths', []) as $relative) {
            $absolute = base_path($relative);
            $passed = is_dir($absolute) && is_writable($absolute);

            $results[] = [
                'path' => $relative,
                'passed' => $passed,
                'label' => $passed
                    ? "{$relative} writable"
                    : "{$relative} is not writable",
            ];
        }

        return $results;
    }

    /**
     * @return array{
     *     passed: bool,
     *     label: string,
     *     available: bool,
     *     drivers: array<string, array{available: bool, label: string, default_port: int, default_host: string}>
     * }
     */
    public function checkDatabaseDriver(): array
    {
        $drivers = [];
        $availableCount = 0;

        foreach ($this->supportedDrivers() as $key => $meta) {
            $available = $this->driverAvailable($key);
            if ($available) {
                $availableCount++;
            }

            $drivers[$key] = [
                'available' => $available,
                'label' => $meta['label'],
                'default_port' => (int) $meta['default_port'],
                'default_host' => (string) $meta['default_host'],
            ];
        }

        $available = $availableCount > 0;
        $labels = collect($drivers)
            ->filter(fn (array $d) => $d['available'])
            ->pluck('label')
            ->values()
            ->all();

        return [
            'passed' => $available,
            'available' => $available,
            'drivers' => $drivers,
            'label' => $available
                ? 'Database drivers available: '.implode(', ', $labels)
                : 'No database driver available (install pdo_pgsql and/or pdo_mysql)',
        ];
    }

    /**
     * @return array<string, array{label: string, extension: string, default_port: int, default_host: string}>
     */
    public function supportedDrivers(): array
    {
        return config('cms.database_drivers', []);
    }

    /**
     * @return array<string, string> type => label (only installed drivers)
     */
    public function availableDriverOptions(): array
    {
        $options = [];

        foreach ($this->supportedDrivers() as $key => $meta) {
            if ($this->driverAvailable($key)) {
                $options[$key] = $meta['label'];
            }
        }

        return $options;
    }

    public function driverAvailable(string $type): bool
    {
        $meta = $this->supportedDrivers()[$type] ?? null;
        if (! $meta) {
            return false;
        }

        $extension = $meta['extension'];
        $pdoDriver = $type === 'mysql' ? 'mysql' : $type;

        return extension_loaded($extension) && in_array($pdoDriver, PDO::getAvailableDrivers(), true);
    }

    public function defaultPort(string $type): int
    {
        return (int) ($this->supportedDrivers()[$type]['default_port'] ?? 5432);
    }

    /**
     * Test a database connection with the given credentials.
     * Never log the password.
     *
     * @param  array{type?: string, host: string, port: string|int, database: string, username: string, password?: string}  $config
     * @return array{success: bool, message: string}
     */
    public function testDatabaseConnection(array $config): array
    {
        $type = $config['type'] ?? 'pgsql';

        if (! $this->driverAvailable($type)) {
            $label = $this->supportedDrivers()[$type]['label'] ?? $type;

            return [
                'success' => false,
                'message' => "The {$label} PHP driver is not installed on this server.",
            ];
        }

        try {
            $dsn = $this->buildDsn($type, $config);

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );

            $pdo->query('SELECT 1');

            $label = $this->supportedDrivers()[$type]['label'] ?? strtoupper($type);

            return [
                'success' => true,
                'message' => "{$label} connection successful",
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Could not connect to the database. Please check your settings and try again.',
            ];
        }
    }

    /**
     * Apply temporary DB config for the current request (without writing .env yet).
     *
     * @param  array{type?: string, host: string, port: string|int, database: string, username: string, password?: string}  $config
     */
    public function configureRuntimeConnection(array $config): void
    {
        $type = $config['type'] ?? 'pgsql';

        if ($type === 'mysql') {
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $config['host'],
                'database.connections.mysql.port' => $config['port'],
                'database.connections.mysql.database' => $config['database'],
                'database.connections.mysql.username' => $config['username'],
                'database.connections.mysql.password' => $config['password'] ?? '',
            ]);

            DB::purge('mysql');
            DB::setDefaultConnection('mysql');
            DB::reconnect('mysql');

            return;
        }

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => $config['host'],
            'database.connections.pgsql.port' => $config['port'],
            'database.connections.pgsql.database' => $config['database'],
            'database.connections.pgsql.username' => $config['username'],
            'database.connections.pgsql.password' => $config['password'] ?? '',
        ]);

        DB::purge('pgsql');
        DB::setDefaultConnection('pgsql');
        DB::reconnect('pgsql');
    }

    /**
     * @param  array{host: string, port: string|int, database: string}  $config
     */
    protected function buildDsn(string $type, array $config): string
    {
        if ($type === 'mysql') {
            return sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );
        }

        return sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );
    }
}
