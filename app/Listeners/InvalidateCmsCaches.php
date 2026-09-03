<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ContentPublished;
use App\Events\ContentUpdated;
use App\Events\SettingsUpdated;
use App\Services\Search\SearchIndexer;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;

final class InvalidateCmsCaches
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SearchIndexer $searchIndexer,
    ) {}

    public function handleContentUpdated(ContentUpdated|ContentPublished $event): void
    {
        Cache::forget('cms.content.queries');
        Cache::forget('cms.menus');
        Cache::forget('cms.taxonomy.trees');
        Cache::forget('sitemap.xml');

        $this->searchIndexer->indexContent($event->content);
    }

    public function handleSettingsUpdated(SettingsUpdated $event): void
    {
        $this->settings->forget($event->key);
    }
}
