@extends('layouts.admin')

@section('title', __('admin.nav.comments'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.nav.comments') }}</h1>
        <p class="page-intro mb-0">{{ __('admin.comments.intro') }}</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.settings.discussion') }}">Discussion settings</a>
</div>

<div class="users-role-tabs mb-3">
    @foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved','spam'=>'Spam','trash'=>'Trash'] as $key => $label)
        <a href="{{ route('admin.comments.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
           class="{{ $status === $key ? 'is-active' : '' }}">
            {{ $label }} <span>({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<div class="panel mb-3">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        @if ($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:280px" placeholder="{{ __('admin.comments.search') }}">
        <button class="btn btn-outline-secondary" type="submit">{{ __('common.search') }}</button>
    </form>
</div>

<form method="POST" action="{{ route('admin.comments.bulk') }}">
    @csrf
    <div class="panel">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <select name="action" class="form-select" style="max-width:200px" required>
                <option value="">Bulk actions</option>
                <option value="approve">Approve</option>
                <option value="unapprove">Unapprove</option>
                <option value="spam">Mark as spam</option>
                <option value="trash">Move to Trash</option>
                <option value="restore">Restore</option>
                <option value="delete">{{ __('admin.comments.delete_permanently') }}</option>
            </select>
            <button class="btn btn-outline-secondary" type="submit">Apply</button>
        </div>

        @forelse ($comments as $comment)
            <div class="comment-mod-row border-bottom py-3">
                <div class="d-flex gap-2 align-items-start">
                    <input type="checkbox" name="comments[]" value="{{ $comment->id }}" class="mt-1">
                    <div class="w-100">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <strong>{{ $comment->displayName() }}</strong>
                                <span class="page-intro"> · {{ $comment->displayEmail() }}</span>
                                <span class="badge text-bg-secondary">{{ $comment->status }}</span>
                            </div>
                            <div class="small">{{ $comment->created_at?->diffForHumans() }}</div>
                        </div>
                        <p class="mb-2 mt-2">{{ $comment->content }}</p>
                        <div class="small">
                            On
                            @if ($comment->post)
                                <a href="{{ route('admin.posts.edit', $comment->post) }}">{{ $comment->post->title }}</a>
                            @else
                                deleted post
                            @endif
                        </div>
                        <div class="row-actions small mt-2">
                            @if ($comment->status !== 'approved')
                                <button form="c-approve-{{ $comment->id }}" type="submit">Approve</button> ·
                            @else
                                <button form="c-pending-{{ $comment->id }}" type="submit">Unapprove</button> ·
                            @endif
                            <button form="c-spam-{{ $comment->id }}" type="submit">Spam</button> ·
                            <button form="c-trash-{{ $comment->id }}" class="link-danger" type="submit">
                                {{ $comment->status === 'trash' ? __('admin.comments.delete_permanently') : __('admin.comments.trash') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">No comments in this view.</div>
        @endforelse

        <div class="mt-3">{{ $comments->links() }}</div>
    </div>
</form>

@foreach ($comments as $comment)
    <form id="c-approve-{{ $comment->id }}" method="POST" action="{{ route('admin.comments.update', $comment) }}" class="d-none">@csrf @method('PUT')<input type="hidden" name="status" value="approved"></form>
    <form id="c-pending-{{ $comment->id }}" method="POST" action="{{ route('admin.comments.update', $comment) }}" class="d-none">@csrf @method('PUT')<input type="hidden" name="status" value="pending"></form>
    <form id="c-spam-{{ $comment->id }}" method="POST" action="{{ route('admin.comments.update', $comment) }}" class="d-none">@csrf @method('PUT')<input type="hidden" name="status" value="spam"></form>
    <form id="c-trash-{{ $comment->id }}" method="POST" action="{{ route('admin.comments.destroy', $comment) }}" class="d-none">@csrf @method('DELETE')</form>
@endforeach
@endsection
