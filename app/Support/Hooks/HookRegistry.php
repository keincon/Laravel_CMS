<?php

declare(strict_types=1);

namespace App\Support\Hooks;

/**
 * Laravel-native action / filter hook registry (WordPress-inspired, not a WP port).
 *
 * Actions: fire side effects with Hooks::action('name', ...$args)
 * Filters: transform a value with Hooks::filter('name', $value, ...$args)
 */
final class HookRegistry
{
    /** @var array<string, list<array{callback: callable, priority: int}>> */
    private array $actions = [];

    /** @var array<string, list<array{callback: callable, priority: int}>> */
    private array $filters = [];

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        foreach ($this->sorted($this->actions[$hook] ?? []) as $listener) {
            ($listener['callback'])(...$args);
        }
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->sorted($this->filters[$hook] ?? []) as $listener) {
            $value = ($listener['callback'])($value, ...$args);
        }

        return $value;
    }

    public function hasAction(string $hook): bool
    {
        return ! empty($this->actions[$hook]);
    }

    public function hasFilter(string $hook): bool
    {
        return ! empty($this->filters[$hook]);
    }

    public function removeAllActions(?string $hook = null): void
    {
        if ($hook === null) {
            $this->actions = [];

            return;
        }

        unset($this->actions[$hook]);
    }

    public function removeAllFilters(?string $hook = null): void
    {
        if ($hook === null) {
            $this->filters = [];

            return;
        }

        unset($this->filters[$hook]);
    }

    /**
     * @param  list<array{callback: callable, priority: int}>  $listeners
     * @return list<array{callback: callable, priority: int}>
     */
    private function sorted(array $listeners): array
    {
        usort($listeners, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $listeners;
    }
}
