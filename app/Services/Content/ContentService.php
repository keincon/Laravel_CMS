<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\ContentType;
use App\Models\User;
use App\Support\Hooks\Hooks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ContentService
{
    public function __construct(
        private readonly ContentStatusTransitionService $transitions,
        private readonly DualWriteContentSync $dualWrite,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(ContentType|string $type, array $data, ?User $author = null): Content
    {
        $contentType = $this->resolveType($type);

        return DB::transaction(function () use ($contentType, $data, $author): Content {
            $status = ContentStatus::fromLegacy((string) ($data['status'] ?? 'draft'));

            $content = Content::query()->create([
                'content_type_id' => $contentType->id,
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($contentType, (string) ($data['slug'] ?? Str::slug((string) $data['title']))),
                'body' => $data['body'] ?? $data['content'] ?? null,
                'blocks' => $data['blocks'] ?? null,
                'excerpt' => $data['excerpt'] ?? null,
                'status' => $status,
                'visibility' => $data['visibility'] ?? 'public',
                'author_id' => $author?->id ?? $data['author_id'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'featured_media_id' => $data['featured_media_id'] ?? null,
                'comment_status' => $data['comment_status'] ?? 'open',
                'template' => $data['template'] ?? null,
                'menu_order' => (int) ($data['menu_order'] ?? 0),
                'published_at' => $data['published_at'] ?? ($status === ContentStatus::Published ? now() : null),
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            if ($contentType->hierarchical && ! empty($data['parent_id'])) {
                $this->assertNoCircularParent($content, (int) $data['parent_id']);
            }

            if (! empty($data['term_ids']) && is_array($data['term_ids'])) {
                $content->terms()->sync(array_map('intval', $data['term_ids']));
            }

            $this->dualWrite->syncFromContent($content->fresh(['type', 'terms.taxonomy']));

            Hooks::action('content.created', $content);
            event(new \App\Events\ContentUpdated($content));

            return $content->fresh(['type', 'author', 'terms']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Content $content, array $data): Content
    {
        return DB::transaction(function () use ($content, $data): Content {
            if (array_key_exists('status', $data)) {
                $target = ContentStatus::fromLegacy((string) $data['status']);
                $current = $content->status instanceof ContentStatus
                    ? $content->status
                    : ContentStatus::fromLegacy((string) $content->status);
                $data['status'] = $this->transitions->transition($current, $target);
            }

            if (! empty($data['parent_id']) && $content->type?->hierarchical) {
                $this->assertNoCircularParent($content, (int) $data['parent_id']);
            }

            if (isset($data['slug'])) {
                $data['slug'] = $this->uniqueSlug($content->type, (string) $data['slug'], $content->id);
            }

            if (isset($data['content']) && ! isset($data['body'])) {
                $data['body'] = $data['content'];
                unset($data['content']);
            }

            $content->fill(collect($data)->only([
                'title', 'slug', 'body', 'blocks', 'excerpt', 'status', 'visibility',
                'password', 'author_id', 'parent_id', 'featured_media_id', 'comment_status',
                'template', 'menu_order', 'published_at', 'scheduled_at',
            ])->all());

            $content->save();

            if (array_key_exists('term_ids', $data) && is_array($data['term_ids'])) {
                $content->terms()->sync(array_map('intval', $data['term_ids']));
            }

            $this->dualWrite->syncFromContent($content->fresh(['type', 'terms.taxonomy']));

            Hooks::action('content.updated', $content);
            event(new \App\Events\ContentUpdated($content));

            $status = $content->status instanceof ContentStatus ? $content->status : null;
            if ($status === ContentStatus::Published) {
                Hooks::action('content.published', $content);
                event(new \App\Events\ContentPublished($content));
            }

            return $content->fresh(['type', 'author', 'terms']);
        });
    }

    public function publish(Content $content): Content
    {
        return $this->update($content, [
            'status' => ContentStatus::Published->value,
            'published_at' => $content->published_at ?? now(),
            'scheduled_at' => null,
        ]);
    }

    public function schedule(Content $content, \DateTimeInterface $when): Content
    {
        return $this->update($content, [
            'status' => ContentStatus::Scheduled->value,
            'scheduled_at' => $when,
            'published_at' => $when,
        ]);
    }

    public function trash(Content $content): Content
    {
        return $this->update($content, ['status' => ContentStatus::Trash->value]);
    }

    public function restore(Content $content): Content
    {
        return $this->update($content, ['status' => ContentStatus::Draft->value]);
    }

    public function duplicate(Content $content, ?User $author = null): Content
    {
        return $this->create($content->type, [
            'title' => $content->title.' (Copy)',
            'body' => $content->body,
            'blocks' => $content->blocks,
            'excerpt' => $content->excerpt,
            'status' => ContentStatus::Draft->value,
            'visibility' => $content->visibility,
            'parent_id' => $content->parent_id,
            'featured_media_id' => $content->featured_media_id,
            'comment_status' => $content->comment_status,
            'template' => $content->template,
            'menu_order' => $content->menu_order,
        ], $author ?? $content->author);
    }

    private function resolveType(ContentType|string $type): ContentType
    {
        if ($type instanceof ContentType) {
            return $type;
        }

        $resolved = ContentType::query()->where('slug', $type)->first();

        if ($resolved === null) {
            throw new InvalidArgumentException("Unknown content type [{$type}].");
        }

        return $resolved;
    }

    private function uniqueSlug(ContentType $type, string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'item';
        $candidate = $base;
        $i = 2;

        while (
            Content::query()
                ->where('content_type_id', $type->id)
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function assertNoCircularParent(Content $content, int $parentId): void
    {
        if ($content->exists && $content->isHierarchicalCycle($parentId)) {
            throw new InvalidArgumentException('Circular parent relationship is not allowed.');
        }
    }
}
