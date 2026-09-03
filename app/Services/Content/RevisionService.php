<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content;
use App\Models\ContentRevision;
use App\Models\User;
use App\Support\Hooks\Hooks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

final class RevisionService
{
    public function snapshot(Content $content, ?User $user = null, ?string $note = null): ContentRevision
    {
        $next = (int) $content->revisions()->max('revision_number') + 1;

        return ContentRevision::query()->create([
            'content_id' => $content->id,
            'revisable_type' => Content::class,
            'revisable_id' => $content->id,
            'user_id' => $user?->id ?? $content->author_id,
            'revision_number' => $next,
            'title' => $content->title,
            'body' => $content->body,
            'excerpt' => $content->excerpt,
            'blocks' => $content->blocks,
            'metadata' => $content->meta()->get()->mapWithKeys(
                fn ($row) => [$row->key => $row->value]
            )->all(),
            'payload' => [
                'title' => $content->title,
                'body' => $content->body,
                'excerpt' => $content->excerpt,
                'blocks' => $content->blocks,
                'status' => $content->status instanceof \BackedEnum
                    ? $content->status->value
                    : $content->status,
            ],
            'note' => $note,
        ]);
    }

    /**
     * @return Collection<int, ContentRevision>
     */
    public function list(Content $content): Collection
    {
        return $content->revisions()->with('user')->get();
    }

    public function restore(Content $content, ContentRevision $revision): Content
    {
        if ($revision->content_id && (int) $revision->content_id !== (int) $content->id) {
            throw new \InvalidArgumentException('Revision does not belong to this content.');
        }

        return DB::transaction(function () use ($content, $revision): Content {
            $this->snapshot($content, null, 'pre-restore');

            $payload = $revision->payload ?? [];

            $content->fill([
                'title' => $revision->title ?? $payload['title'] ?? $content->title,
                'body' => $revision->body ?? $payload['body'] ?? $content->body,
                'excerpt' => $revision->excerpt ?? $payload['excerpt'] ?? $content->excerpt,
                'blocks' => $revision->blocks ?? $payload['blocks'] ?? $content->blocks,
            ]);
            $content->save();

            Hooks::action('content.revision.restored', $content, $revision);

            return $content->fresh();
        });
    }
}
