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
     *     database: array{passed: bool, label: string, available: bool}
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
     * @return array{passed: bool, label: string, available: bool}
     */
    public function checkDatabaseDriver(): array
    {
        $available = extension_loaded('pdo_pgsql') && in_array('pgsql', PDO::getAvailableDrivers(), true);

        return [
            'passed' => $available,
            'available' => $available,
            'label' => $available
                ? 'PostgreSQL available'
                : 'PostgreSQL driver (pdo_pgsql) is not available',
        ];
    }

    /**
     * Test a PostgreSQL connection with the given credentials.
     * Never log the password.
     *
     * @param  array{host: string, port: string|int, database: string, username: string, password?: string}  $config
     * @return array{success: bool, message: string}
     */
    public function testDatabaseConnection(array $config): array
    {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'],
                $config['database']
            );

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

            return [
                'success' => true,
                'message' => 'Database connection successful',
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
     * @param  array{host: string, port: string|int, database: string, username: string, password?: string}  $config
     */
    public function configureRuntimeConnection(array $config): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => $config['host'],
            'database.connections.pgsql.port' => $config['port'],
            'database.connections.pgsql.database' => $config['database'],
            'database.connections.pgsql.username' => $config['username'],
            'database.connections.pgsql.password' => $config['password'] ?? '',
        ]);

        DB::purge('pgsql');
        DB::reconnect('pgsql');
    }
}
