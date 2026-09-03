<?php

declare(strict_types=1);

namespace App\Support\Widgets;

final class WidgetRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $widgets = [];

    /**
     * @param  array<string, mixed>  $definition
     */
    public function register(string $id, array $definition): void
    {
        $this->widgets[$id] = array_merge(['id' => $id], $definition);
    }

    public function registerDefaults(): void
    {
        foreach ([
            'recent_posts' => 'Recent Posts',
            'search' => 'Search',
            'categories' => 'Categories',
            'tags' => 'Tags',
            'archives' => 'Archives',
            'text' => 'Text',
            'custom_html' => 'Custom HTML',
            'menu' => 'Menu',
        ] as $id => $label) {
            $this->register($id, ['label' => $label]);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    public function has(string $id): bool
    {
        return isset($this->widgets[$id]);
    }
}
