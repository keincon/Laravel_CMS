<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ContentStatus;
use App\Models\Content;
use App\Services\Content\ContentService;
use App\Support\Hooks\Hooks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent scheduled publishing job.
 */
class PublishScheduledContent implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $contentId) {}

    public function handle(ContentService $contents): void
    {
        DB::transaction(function () use ($contents): void {
            /** @var Content|null $content */
            $content = Content::query()->lockForUpdate()->find($this->contentId);

            if ($content === null) {
                return;
            }

            $status = $content->status instanceof ContentStatus
                ? $content->status
                : ContentStatus::fromLegacy((string) $content->status);

            if ($status !== ContentStatus::Scheduled) {
                return;
            }

            if ($content->scheduled_at && $content->scheduled_at->isFuture()) {
                return;
            }

            $contents->publish($content);
            Hooks::action('content.published', $content->fresh());
        });
    }
}
