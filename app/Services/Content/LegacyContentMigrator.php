<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content;
use App\Models\ContentType;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Support\Facades\DB;

/**
 * Least-destructive bridge: backfill legacy posts/pages into contents.
 */
final class LegacyContentMigrator
{
    public function __construct(private readonly DualWriteContentSync $dualWrite) {}

    public function migrateAll(): array
    {
        return DB::transaction(function (): array {
            $pageType = ContentType::query()->where('slug', 'page')->firstOrFail();

            $posts = 0;
            $pages = 0;

            foreach (Post::query()->with(['categories', 'tags'])->cursor() as $post) {
                $this->dualWrite->syncPost($post, force: true);
                $posts++;
            }

            foreach (Page::query()->withTrashed()->cursor() as $page) {
                $this->dualWrite->syncPage($page, force: true);
                $pages++;
            }

            // Second pass: map page hierarchy by slug (pages may soft-delete).
            foreach (Page::query()->withTrashed()->whereNotNull('parent_id')->cursor() as $page) {
                $parent = Page::query()->withTrashed()->find($page->parent_id);
                if (! $parent) {
                    continue;
                }
                $childContent = Content::query()
                    ->where('content_type_id', $pageType->id)
                    ->where('slug', $page->slug)
                    ->first();
                $parentContent = Content::query()
                    ->where('content_type_id', $pageType->id)
                    ->where('slug', $parent->slug)
                    ->first();
                if ($childContent && $parentContent) {
                    $childContent->update(['parent_id' => $parentContent->id]);
                }
            }

            return ['posts' => $posts, 'pages' => $pages];
        });
    }

    public function createSlugRedirect(string $from, string $to, int $code = 301): Redirect
    {
        return Redirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => $code, 'is_active' => true],
        );
    }
}
