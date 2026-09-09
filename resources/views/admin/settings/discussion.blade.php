@extends('layouts.admin')

@section('title', __('admin.nav.discussion'))

@section('content')
<p class="page-intro mb-4">{{ __('admin.settings.discussion_intro') }}</p>
<form method="POST" action="{{ route('admin.settings.discussion.update') }}" class="settings-form panel">
    @csrf
    @method('PUT')
    <label class="capability-item mb-3">
        <input type="checkbox" name="comments_enabled" value="1" @checked($commentsEnabled)>
        <span>
            <strong>{{ __('admin.settings.comments_enabled') }}</strong>
            <small>{{ __('admin.settings.comments_enabled_hint') }}</small>
        </span>
    </label>
    <label class="capability-item mb-3">
        <input type="checkbox" name="comment_moderation" value="1" @checked($commentModeration)>
        <span>
            <strong>{{ __('admin.settings.comment_moderation') }}</strong>
            <small>{{ __('admin.settings.comment_moderation_hint') }}</small>
        </span>
    </label>
    <div class="mb-3">
        <label class="form-label">{{ __('admin.settings.default_comment_status') }}</label>
        <select name="default_comment_status" class="form-select">
            <option value="open" @selected($defaultCommentStatus === 'open')>{{ __('admin.settings.comment_status_open') }}</option>
            <option value="closed" @selected($defaultCommentStatus === 'closed')>{{ __('admin.settings.comment_status_closed') }}</option>
        </select>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ __('admin.settings.save_changes') }}</button>
    </div>
</form>
@endsection
