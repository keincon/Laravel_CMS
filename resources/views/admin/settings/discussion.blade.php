@extends('layouts.admin')

@section('title', __('admin.nav.discussion'))

@section('content')
<p class="page-intro mb-4">{{ __('admin.settings.discussion_intro') }}</p>
<form method="POST" action="{{ route('admin.settings.discussion.update') }}" class="settings-form panel">
    @csrf
    @method('PUT')
    <label class="capability-item mb-3">
        <input type="checkbox" name="comments_enabled" value="1" @checked($commentsEnabled)>
        <span><strong>Allow comments on posts</strong><small>Site-wide comments switch</small></span>
    </label>
    <label class="capability-item mb-3">
        <input type="checkbox" name="comment_moderation" value="1" @checked($commentModeration)>
        <span><strong>Comment must be manually approved</strong><small>Guest comments stay pending until approved</small></span>
    </label>
    <div class="mb-3">
        <label class="form-label">Default comment status for new posts</label>
        <select name="default_comment_status" class="form-select">
            <option value="open" @selected($defaultCommentStatus === 'open')>Open</option>
            <option value="closed" @selected($defaultCommentStatus === 'closed')>Closed</option>
        </select>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
@endsection
