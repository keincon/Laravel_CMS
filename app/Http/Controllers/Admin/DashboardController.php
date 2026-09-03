<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Content;
use App\Models\ContentRevision;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\Content\ContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $recentContents = Content::query()
            ->with(['author', 'type'])
            ->where('status', '!=', 'trash')
            ->latest()
            ->limit(8)
            ->get();

        $recentComments = Comment::query()->with(['post', 'user'])->latest()->limit(5)->get();
        $activity = ContentRevision::query()->with('user')->latest()->limit(8)->get();

        return view('admin.dashboard', [
            'stats' => [
                'contents' => Content::query()->where('status', '!=', 'trash')->count(),
                'pages' => Content::query()->ofType('page')->where('status', '!=', 'trash')->count()
                    ?: Page::query()->count(),
                'posts' => Content::query()->ofType('post')->where('status', '!=', 'trash')->count()
                    ?: Post::query()->notTrashed()->count(),
                'published_posts' => Content::query()->ofType('post')->published()->count()
                    ?: Post::query()->where('status', 'publish')->count(),
                'draft_posts' => Content::query()->ofType('post')->where('status', 'draft')->count()
                    ?: Post::query()->where('status', 'draft')->count(),
                'categories' => Category::query()->count(),
                'tags' => Tag::query()->count(),
                'media' => Media::query()->count(),
                'comments' => Comment::query()->whereNotIn('status', ['trash', 'spam'])->count(),
                'pending_comments' => Comment::query()->where('status', 'pending')->count(),
                'users' => User::query()->count(),
            ],
            'recentPosts' => $recentContents->filter(fn ($c) => $c->type?->slug === 'post')->take(5)->values(),
            'recentPages' => $recentContents->filter(fn ($c) => $c->type?->slug === 'page')->take(5)->values(),
            'recentContents' => $recentContents,
            'recentComments' => $recentComments,
            'activity' => $activity,
        ]);
    }

    public function quickDraft(Request $request, ContentService $contents): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $content = $contents->create('post', [
            'title' => $data['title'],
            'body' => $data['content'] ?? '',
            'status' => 'draft',
            'comment_status' => 'open',
            'template' => 'default',
        ], $request->user());

        return redirect()
            ->route('admin.contents.edit', $content)
            ->with('success', 'Draft saved. Continue editing.');
    }
}
