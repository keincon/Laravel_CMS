@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<p class="page-intro mb-4">At a Glance — your site activity, like WordPress.</p>

<div class="stat-grid mb-4">
    <div class="stat-card">
        <div class="label">Posts</div>
        <div class="value">{{ $stats['posts'] }}</div>
        <div class="hint">{{ $stats['published_posts'] }} published · {{ $stats['draft_posts'] }} drafts</div>
    </div>
    <div class="stat-card">
        <div class="label">Pages</div>
        <div class="value">{{ $stats['pages'] }}</div>
        <div class="hint"><a href="{{ route('admin.pages.index') }}">Manage pages →</a></div>
    </div>
    <div class="stat-card">
        <div class="label">Comments</div>
        <div class="value">{{ $stats['comments'] }}</div>
        <div class="hint">
            @if ($stats['pending_comments'] > 0)
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}">{{ $stats['pending_comments'] }} pending →</a>
            @else
                <a href="{{ route('admin.comments.index') }}">Moderate →</a>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="label">Media</div>
        <div class="value">{{ $stats['media'] }}</div>
        <div class="hint">{{ $stats['users'] }} users · {{ $stats['categories'] }} categories</div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="panel h-100">
            <h2 class="h6 mb-3">Quick Draft</h2>
            <form method="POST" action="{{ route('admin.dashboard.quick-draft') }}">
                @csrf
                <div class="mb-2">
                    <input name="title" class="form-control" placeholder="Title" required>
                </div>
                <div class="mb-2">
                    <textarea name="content" class="form-control" rows="4" placeholder="What’s on your mind?"></textarea>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Save Draft</button>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Recent Comments</h2>
                <a href="{{ route('admin.comments.index') }}" class="small">View all</a>
            </div>
            @forelse ($recentComments as $comment)
                <div class="py-2 border-bottom">
                    <div class="small"><strong>{{ $comment->displayName() }}</strong> on
                        @if ($comment->post)
                            <a href="{{ route('admin.posts.edit', $comment->post) }}">{{ \Illuminate\Support\Str::limit($comment->post->title, 28) }}</a>
                        @endif
                    </div>
                    <div class="small">{{ \Illuminate\Support\Str::limit($comment->content, 70) }}</div>
                </div>
            @empty
                <p class="small mb-0">No comments yet.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Activity</h2>
            </div>
            @forelse ($activity as $item)
                <div class="small py-2 border-bottom">
                    {{ $item->note ?: 'Revision' }}
                    <span class="page-intro">· {{ $item->user?->name }} · {{ $item->created_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="small mb-0">No recent activity.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="panel">
            <div class="d-flex justify-content-between mb-2">
                <h2 class="h6 mb-0">Recent Posts</h2>
                <a href="{{ route('admin.contents.index', ['type' => 'post']) }}" class="small">View all</a>
            </div>
            @forelse ($recentPosts as $post)
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <a href="{{ route('admin.contents.edit', $post) }}">{{ $post->title }}</a>
                    <span class="badge text-bg-secondary">{{ $post->status instanceof \BackedEnum ? $post->status->value : $post->status }}</span>
                </div>
            @empty
                <p class="small mb-0">No posts yet.</p>
            @endforelse
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel">
            <div class="d-flex justify-content-between mb-2">
                <h2 class="h6 mb-0">Recent Pages</h2>
                <a href="{{ route('admin.contents.index', ['type' => 'page']) }}" class="small">View all</a>
            </div>
            @forelse ($recentPages as $page)
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <a href="{{ route('admin.contents.edit', $page) }}">{{ $page->title }}</a>
                    <span class="badge text-bg-secondary">{{ $page->status instanceof \BackedEnum ? $page->status->value : $page->status }}</span>
                </div>
            @empty
                <p class="small mb-0">No pages yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
