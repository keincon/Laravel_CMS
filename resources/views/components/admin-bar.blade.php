@auth
@php
    $user = auth()->user();
    $editUrl = null;
    if (! empty($post) && $post?->exists) {
        $editUrl = route('admin.posts.edit', $post);
    } elseif (! empty($page) && $page?->exists) {
        $editUrl = route('admin.pages.edit', $page);
    }
@endphp
<div class="cms-admin-bar" style="position:sticky;top:0;z-index:100;background:#1d2327;color:#f0f0f1;font:13px/32px 'Segoe UI',system-ui,sans-serif;">
    <div style="display:flex;align-items:center;gap:.25rem;padding:0 .75rem;flex-wrap:wrap;">
        <a href="{{ route('admin.dashboard') }}" style="color:#fff;text-decoration:none;padding:0 .65rem;font-weight:650;">{{ __('admin.admin_bar.dashboard') }}</a>
        <a href="{{ route('admin.contents.create', ['type' => 'post']) }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">{{ __('admin.admin_bar.new_post') }}</a>
        <a href="{{ route('admin.contents.create', ['type' => 'page']) }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">{{ __('admin.admin_bar.new_page') }}</a>
        <a href="{{ route('admin.media.index') }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">{{ __('admin.admin_bar.media') }}</a>
        <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">{{ __('admin.admin_bar.comments') }}</a>
        @if ($editUrl)
            <a href="{{ $editUrl }}" style="color:#72aee6;text-decoration:none;padding:0 .65rem;font-weight:650;">{{ __('admin.admin_bar.edit') }}</a>
        @endif
        <span style="margin-left:auto;opacity:.85;padding:0 .65rem;">{{ $user->name }}</span>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">@csrf
            <button type="submit" style="background:transparent;border:0;color:#f0f0f1;cursor:pointer;padding:0 .65rem;font:inherit;">{{ __('admin.admin_bar.log_out') }}</button>
        </form>
    </div>
</div>
<style>body.has-admin-bar { }</style>
<script>document.documentElement.classList.add('has-cms-admin-bar');</script>
@endauth
