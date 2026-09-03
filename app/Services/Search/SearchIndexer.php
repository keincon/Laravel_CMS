<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Content;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes published content documents into Meilisearch when configured.
 */
final class SearchIndexer
{
    public function indexContent(Content $content): void
    {
        if (config('cms.search.driver') !== 'meilisearch') {
            return;
        }

        $host = rtrim((string) config('cms.search.meilisearch.host'), '/');
        if ($host === '') {
            return;
        }

        $content->loadMissing('type', 'author');
        $index = (string) config('cms.search.meilisearch.index', 'laravelpress');
        $key = config('cms.search.meilisearch.key');

        $request = Http::timeout(5)->acceptJson();
        if (filled($key)) {
            $request = $request->withHeaders(['Authorization' => 'Bearer '.$key]);
        }

        $doc = [
            'id' => $content->id,
            'type' => 'content:'.($content->type?->slug ?? 'content'),
            'title' => $content->title,
            'excerpt' => $content->excerpt,
            'body' => $content->body,
            'url' => '/'.$content->slug,
            'status' => $content->status?->value ?? (string) $content->status,
        ];

        try {
            $response = $request->post("{$host}/indexes/{$index}/documents", [$doc]);
            if (! $response->successful()) {
                Log::warning('Meilisearch index failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Meilisearch index exception: '.$e->getMessage());
        }
    }

    public function deleteContent(Content $content): void
    {
        if (config('cms.search.driver') !== 'meilisearch') {
            return;
        }

        $host = rtrim((string) config('cms.search.meilisearch.host'), '/');
        if ($host === '') {
            return;
        }

        $index = (string) config('cms.search.meilisearch.index', 'laravelpress');
        $key = config('cms.search.meilisearch.key');

        $request = Http::timeout(5)->acceptJson();
        if (filled($key)) {
            $request = $request->withHeaders(['Authorization' => 'Bearer '.$key]);
        }

        try {
            $request->delete("{$host}/indexes/{$index}/documents/{$content->id}");
        } catch (\Throwable $e) {
            Log::warning('Meilisearch delete exception: '.$e->getMessage());
        }
    }
}
