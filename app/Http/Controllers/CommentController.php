<?php

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Models\CmsSetting;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->status === 'publish', 404);
        abort_unless($post->isCommentsOpen(), 403, 'Comments are closed.');

        return $this->storeComment($request, post: $post, content: Content::query()
            ->ofType('post')
            ->where('slug', $post->slug)
            ->first());
    }

    public function storeForContent(Request $request, Content $content): RedirectResponse
    {
        abort_unless($content->type?->slug === 'post', 404);
        $status = $content->status instanceof ContentStatus
            ? $content->status
            : ContentStatus::fromLegacy((string) $content->status);
        abort_unless($status === ContentStatus::Published, 404);
        abort_unless($content->isCommentsOpen(), 403, 'Comments are closed.');

        $legacy = Post::query()->where('slug', $content->slug)->first();

        return $this->storeComment($request, post: $legacy, content: $content);
    }

    private function storeComment(Request $request, ?Post $post, ?Content $content): RedirectResponse
    {
        abort_unless(CmsSetting::getValue('comments_enabled', true), 403, 'Comments are disabled.');
        abort_unless($post !== null || $content !== null, 404);

        $rules = [
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ];

        if (! $request->user()) {
            $rules['author_name'] = ['required', 'string', 'max:120'];
            $rules['author_email'] = ['required', 'email', 'max:255'];
            $rules['author_url'] = ['nullable', 'url', 'max:255'];
        }

        $data = $request->validate($rules);

        if (! empty($data['parent_id'])) {
            $parentQuery = Comment::query();
            if ($content) {
                $parentQuery->where(function ($q) use ($content, $post) {
                    $q->where('content_id', $content->id);
                    if ($post) {
                        $q->orWhere('post_id', $post->id);
                    }
                });
            } elseif ($post) {
                $parentQuery->where('post_id', $post->id);
            }
            $parent = $parentQuery->findOrFail($data['parent_id']);
            abort_unless($parent->status === 'approved', 422);
        }

        $requireModeration = (bool) CmsSetting::getValue('comment_moderation', true);
        $status = 'pending';
        if ($request->user()?->can('manage_comments') || $request->user()?->isAdministrator()) {
            $status = 'approved';
        } elseif (! $requireModeration) {
            $status = 'approved';
        }

        // Prefer content_id; post_id is optional after legacy retirement.
        $postId = $post?->id;
        if ($postId === null && $content) {
            $synced = app(\App\Services\Content\DualWriteContentSync::class)
                ->syncFromContent($content, force: false);
            if ($synced instanceof Post) {
                $postId = $synced->id;
            }
        }

        abort_unless($postId !== null || $content !== null, 404);

        Comment::query()->create([
            'post_id' => $postId,
            'content_id' => $content?->id,
            'user_id' => $request->user()?->id,
            'parent_id' => $data['parent_id'] ?? null,
            'author_name' => $request->user()?->name ?? ($data['author_name'] ?? null),
            'author_email' => $request->user()?->email ?? ($data['author_email'] ?? null),
            'author_url' => $data['author_url'] ?? null,
            'author_ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'content' => $data['content'],
            'status' => $status,
        ]);

        $this->notifyNewComment(
            author: $request->user()?->name ?? ($data['author_name'] ?? ''),
            email: $request->user()?->email ?? ($data['author_email'] ?? ''),
            body: $data['content'],
            postTitle: $content?->title ?? $post?->title ?? '',
        );

        $message = $status === 'approved'
            ? 'Your comment has been posted.'
            : 'Your comment is awaiting moderation.';

        return back()->with('success', $message);
    }

    private function notifyNewComment(string $author, string $email, string $body, string $postTitle): void
    {
        try {
            $templates = app(\App\Services\MailTemplateService::class);
            $mail = app(\App\Services\MailSettingsService::class)->current();
            $to = (string) ($mail['from_address'] ?? '');
            if ($to === '') {
                return;
            }

            $templates->send('comment_notification', $to, [
                'post_title' => $postTitle,
                'comment_author' => $author,
                'comment_email' => $email,
                'comment_content' => e($body),
                'comment_moderate_url' => route('admin.comments.index'),
            ]);
        } catch (\Throwable) {
            // Mail misconfiguration must not block public comments.
        }
    }
}
