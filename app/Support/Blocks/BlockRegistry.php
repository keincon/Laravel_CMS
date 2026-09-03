<?php

declare(strict_types=1);

namespace App\Support\Blocks;

final class BlockRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $blocks = [];

    /**
     * @param  array<string, mixed>  $definition
     */
    public function register(string $name, array $definition): void
    {
        $this->blocks[$name] = array_merge([
            'name' => $name,
            'label' => $name,
            'attributes' => [],
        ], $definition);
    }

    public function has(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $name): ?array
    {
        return $this->blocks[$name] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    public function registerDefaults(): void
    {
        foreach ([
            'paragraph', 'heading', 'image', 'gallery', 'quote', 'list',
            'video', 'audio', 'button', 'columns', 'separator', 'spacer',
            'code', 'html', 'embed',
        ] as $name) {
            $this->register($name, ['label' => ucfirst($name)]);
        }
    }
}
