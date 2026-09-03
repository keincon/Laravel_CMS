<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Content;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class SearchService
{
    public function __construct(private readonly SearchDriver $driver) {}

    /**
     * @return Collection<int, array{type: string, id: int, title: string, url: string|null, excerpt: string|null}>
     */
    public function search(string $query, array $types = [], int $limit = 50): Collection
    {
        return $this->driver->search($query, $types, $limit);
    }

    public function paginateContents(string $query, int $perPage = 20): LengthAwarePaginator
    {
        $like = '%'.trim($query).'%';

        return Content::query()
            ->published()
            ->with(['type', 'author'])
            ->when(trim($query) !== '', function ($builder) use ($like) {
                $builder->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            })
            ->latest('published_at')
            ->paginate($perPage);
    }
}
