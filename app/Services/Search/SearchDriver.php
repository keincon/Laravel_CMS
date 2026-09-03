<?php

declare(strict_types=1);

namespace App\Services\Search;

use Illuminate\Support\Collection;

interface SearchDriver
{
    /**
     * @return Collection<int, array{type: string, id: int, title: string, url: string|null, excerpt: string|null}>
     */
    public function search(string $query, array $types = [], int $limit = 50): Collection;
}
