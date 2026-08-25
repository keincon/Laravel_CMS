@props(['posts', 'layout' => 'list'])

@if ($layout === 'grid')
    <div class="post-grid">
        @forelse ($posts as $post)
            <x-post-card :post="$post" />
        @empty
            <p style="opacity:.7">No posts found.</p>
        @endforelse
    </div>
@else
    <div class="post-list">
        @forelse ($posts as $post)
            <x-post-card :post="$post" />
        @empty
            <p style="opacity:.7">No posts found.</p>
        @endforelse
    </div>
@endif
