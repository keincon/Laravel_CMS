<?php

declare(strict_types=1);

namespace App\Services\Search;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Meilisearch HTTP search driver (Phase 12).
 * Requires CMS_SEARCH_DRIVER=meilisearch and MEILISEARCH_HOST.
 */
final class MeilisearchSearchDriver implements SearchDriver
{
    public function search(string $query, array $types = [], int $limit = 50): Collection
    {
        $host = rtrim((string) config('cms.search.meilisearch.host'), '/');
        if ($host === '') {
            throw new RuntimeException('Meilisearch is not configured. Set MEILISEARCH_HOST or use database driver.');
        }

        $index = (string) config('cms.search.meilisearch.index', 'laravelpress');
        $key = config('cms.search.meilisearch.key');

        $request = Http::timeout(5)->acceptJson();
        if (filled($key)) {
            $request = $request->withHeaders(['Authorization' => 'Bearer '.$key]);
        }

        $response = $request->post("{$host}/indexes/{$index}/search", [
            'q' => $query,
            'limit' => $limit,
            'filter' => $this->typeFilter($types),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Meilisearch search failed: HTTP '.$response->status());
        }

        $hits = $response->json('hits') ?? [];

        return collect($hits)->map(function (array $hit) {
            return [
                'type' => (string) ($hit['type'] ?? 'content'),
                'id' => (int) ($hit['id'] ?? 0),
                'title' => (string) ($hit['title'] ?? ''),
                'url' => $hit['url'] ?? null,
                'excerpt' => $hit['excerpt'] ?? null,
            ];
        })->values();
    }

    /**
     * @param  list<string>  $types
     */
    private function typeFilter(array $types): ?string
    {
        if ($types === []) {
            return null;
        }

        $quoted = array_map(
            fn (string $type) => '"'.str_replace('"', '\\"', $type).'"',
            $types
        );

        return 'type IN ['.implode(', ', $quoted).']';
    }
}
