@auth
@php
    $user = auth()->user();
    $editUrl = null;
    if (!empty($post) && $post?->exists) {
        $editUrl = route('admin.posts.edit', $post);
    } elseif (!empty($page) && $page?->exists) {
        $editUrl = route('admin.pages.edit', $page);
    }
@endphp
<div class="cms-admin-bar" style="position:sticky;top:0;z-index:100;background:#1d2327;color:#f0f0f1;font:13px/32px 'Segoe UI',system-ui,sans-serif;">
    <div style="display:flex;align-items:center;gap:.25rem;padding:0 .75rem;flex-wrap:wrap;">
        <a href="{{ route('admin.dashboard') }}" style="color:#fff;text-decoration:none;padding:0 .65rem;font-weight:650;">Dashboard</a>
        <a href="{{ route('admin.posts.create') }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">+ New Post</a>
        <a href="{{ route('admin.pages.create') }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">+ New Page</a>
        <a href="{{ route('admin.media.index') }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">Media</a>
        <a href="{{ route('admin.comments.index', ['status'=>'pending']) }}" style="color:#fff;text-decoration:none;padding:0 .65rem;">Comments</a>
        @if ($editUrl)
            <a href="{{ $editUrl }}" style="color:#72aee6;text-decoration:none;padding:0 .65rem;font-weight:650;">Edit</a>
        @endif
        <span style="margin-left:auto;opacity:.85;padding:0 .65rem;">{{ $user->name }}</span>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">@csrf
            <button type="submit" style="background:transparent;border:0;color:#f0f0f1;cursor:pointer;padding:0 .65rem;font:inherit;">Log Out</button>
        </form>
    </div>
</div>
<style>body.has-admin-bar { }</style>
<script>document.documentElement.classList.add('has-cms-admin-bar');</script>
@endauth
