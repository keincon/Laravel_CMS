<?php

declare(strict_types=1);

namespace App\Support\Hooks;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void addAction(string $hook, callable $callback, int $priority = 10)
 * @method static void addFilter(string $hook, callable $callback, int $priority = 10)
 * @method static void doAction(string $hook, mixed ...$args)
 * @method static mixed applyFilters(string $hook, mixed $value, mixed ...$args)
 * @method static bool hasAction(string $hook)
 * @method static bool hasFilter(string $hook)
 * @method static void removeAllActions(?string $hook = null)
 * @method static void removeAllFilters(?string $hook = null)
 *
 * @see HookRegistry
 */
class Hooks extends Facade
{
    /**
     * Fire an action hook.
     */
    public static function action(string $hook, mixed ...$args): void
    {
        static::doAction($hook, ...$args);
    }

    /**
     * Apply a filter hook and return the transformed value.
     */
    public static function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return static::applyFilters($hook, $value, ...$args);
    }

    protected static function getFacadeAccessor(): string
    {
        return HookRegistry::class;
    }
}
