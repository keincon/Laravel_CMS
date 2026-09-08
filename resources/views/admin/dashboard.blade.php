@extends('layouts.admin')

@section('title', __('admin.dashboard.title'))

@section('content')
<p class="page-intro mb-3">{{ __('admin.dashboard.at_a_glance') }}</p>
<p class="mb-4">
    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.help.setup-guide') }}">{{ __('admin.dashboard.setup_guide') }}</a>
</p>

<div class="stat-grid mb-4">
    <div class="stat-card">
        <div class="label">{{ __('admin.dashboard.posts') }}</div>
        <div class="value">{{ $stats['posts'] }}</div>
        <div class="hint">{{ __('admin.dashboard.published_drafts', ['published' => $stats['published_posts'], 'drafts' => $stats['draft_posts']]) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">{{ __('admin.dashboard.pages') }}</div>
        <div class="value">{{ $stats['pages'] }}</div>
        <div class="hint"><a href="{{ route('admin.pages.index') }}">{{ __('admin.dashboard.manage_pages') }}</a></div>
    </div>
    <div class="stat-card">
        <div class="label">{{ __('admin.dashboard.comments') }}</div>
        <div class="value">{{ $stats['comments'] }}</div>
        <div class="hint">
            @if ($stats['pending_comments'] > 0)
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}">{{ __('admin.dashboard.pending', ['count' => $stats['pending_comments']]) }}</a>
            @else
                <a href="{{ route('admin.comments.index') }}">{{ __('admin.dashboard.moderate') }}</a>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="label">{{ __('admin.dashboard.media') }}</div>
        <div class="value">{{ $stats['media'] }}</div>
        <div class="hint">{{ __('admin.dashboard.users_categories', ['users' => $stats['users'], 'categories' => $stats['categories']]) }}</div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="panel h-100">
            <h2 class="h6 mb-3">{{ __('admin.dashboard.quick_draft') }}</h2>
            <form method="POST" action="{{ route('admin.dashboard.quick-draft') }}">
                @csrf
                <div class="mb-2">
                    <input name="title" class="form-control" placeholder="{{ __('admin.dashboard.title_placeholder') }}" required>
                </div>
                <div class="mb-2">
                    <textarea name="content" class="form-control" rows="4" placeholder="{{ __('admin.dashboard.whats_on_mind') }}"></textarea>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">{{ __('admin.dashboard.save_draft') }}</button>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">{{ __('admin.dashboard.recent_comments') }}</h2>
                <a href="{{ route('admin.comments.index') }}" class="small">{{ __('admin.dashboard.view_all') }}</a>
            </div>
            @forelse ($recentComments as $comment)
                <div class="py-2 border-bottom">
                    <div class="small"><strong>{{ $comment->displayName() }}</strong> {{ __('admin.dashboard.comment_on') }}
                        @if ($comment->post)
                            <a href="{{ route('admin.posts.edit', $comment->post) }}">{{ \Illuminate\Support\Str::limit($comment->post->title, 28) }}</a>
                        @endif
                    </div>
                    <div class="small">{{ \Illuminate\Support\Str::limit($comment->content, 70) }}</div>
                </div>
            @empty
                <p class="small mb-0">{{ __('admin.dashboard.no_comments') }}</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">{{ __('admin.dashboard.activity') }}</h2>
            </div>
            @forelse ($activity as $item)
                <div class="small py-2 border-bottom">
                    {{ $item->note ?: __('admin.dashboard.revision') }}
                    <span class="page-intro">· {{ $item->user?->name }} · {{ $item->created_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="small mb-0">{{ __('admin.dashboard.no_activity') }}</p>
            @endforelse
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="panel">
            <div class="d-flex justify-content-between mb-2">
                <h2 class="h6 mb-0">{{ __('admin.dashboard.recent_posts') }}</h2>
                <a href="{{ route('admin.contents.index', ['type' => 'post']) }}" class="small">{{ __('admin.dashboard.view_all') }}</a>
            </div>
            @forelse ($recentPosts as $post)
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <a href="{{ route('admin.contents.edit', $post) }}">{{ $post->title }}</a>
                    <span class="badge text-bg-secondary">{{ $post->status instanceof \BackedEnum ? $post->status->value : $post->status }}</span>
                </div>
            @empty
                <p class="small mb-0">{{ __('admin.dashboard.no_posts') }}</p>
            @endforelse
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel">
            <div class="d-flex justify-content-between mb-2">
                <h2 class="h6 mb-0">{{ __('admin.dashboard.recent_pages') }}</h2>
                <a href="{{ route('admin.contents.index', ['type' => 'page']) }}" class="small">{{ __('admin.dashboard.view_all') }}</a>
            </div>
            @forelse ($recentPages as $page)
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <a href="{{ route('admin.contents.edit', $page) }}">{{ $page->title }}</a>
                    <span class="badge text-bg-secondary">{{ $page->status instanceof \BackedEnum ? $page->status->value : $page->status }}</span>
                </div>
            @empty
                <p class="small mb-0">{{ __('admin.dashboard.no_pages') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
