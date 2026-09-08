<?php

declare(strict_types=1);

namespace App\Support\Plugins;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Creates a starter LaravelPress plugin on disk.
 */
final class PluginScaffoldService
{
    public function __construct(private readonly PluginManager $plugins) {}

    /**
     * @return array{slug: string, path: string}
     */
    public function scaffold(string $slug, ?string $name = null): array
    {
        $slug = Str::slug($slug);
        if ($slug === '') {
            throw new \InvalidArgumentException('Invalid plugin slug.');
        }

        $path = $this->plugins->pluginsPath().DIRECTORY_SEPARATOR.$slug;
        if (File::isDirectory($path) || $this->plugins->has($slug)) {
            throw new \InvalidArgumentException("Plugin [{$slug}] already exists.");
        }

        $display = $name ?: Str::headline($slug);
        $classBase = Str::studly(str_replace('-', '_', $slug));
        $providerClass = 'Plugins\\'.$classBase.'\\PluginServiceProvider';

        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'src');
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'routes');
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views');
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'assets');

        $manifest = [
            'name' => $display,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => '',
            'description' => "LaravelPress plugin: {$display}",
            'provider' => $providerClass,
            'provider_file' => 'src/PluginServiceProvider.php',
        ];

        File::put(
            $path.DIRECTORY_SEPARATOR.'plugin.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        File::put($path.DIRECTORY_SEPARATOR.'hooks.php', <<<'PHP'
<?php

declare(strict_types=1);

use App\Support\Hooks\Hooks;

/**
 * Lightweight hook registrations (runs when the plugin is active).
 */
Hooks::addAction('laravelpress.plugin.booted', static function () {
    //
});

PHP
        );

        File::put($path.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'PluginServiceProvider.php', <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$classBase};

use App\Support\Hooks\Hooks;
use Illuminate\Support\ServiceProvider;

final class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Hooks::addAction('laravelpress.plugin.{$slug}.booted', static function (): void {});
        Hooks::action('laravelpress.plugin.{$slug}.booted');
    }
}

PHP
        );

        File::put($path.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php', <<<'PHP'
<?php

declare(strict_types=1);

// Optional plugin routes. Loaded only while the plugin is active.
// use Illuminate\Support\Facades\Route;

PHP
        );

        File::put($path.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'readme.txt', "Assets for {$display}\n");

        $this->plugins->discover();
        $this->plugins->syncDiskToDatabase();

        return ['slug' => $slug, 'path' => $path];
    }
}
