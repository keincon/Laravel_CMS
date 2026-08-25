@props(['context' => 'page'])

@php
    $recent = \App\Models\Post::query()->published()->latest('published_at')->limit(5)->get();
    $categories = \App\Models\Category::query()->orderBy('name')->limit(10)->get();
@endphp

<div style="background:var(--color-surface);border:1px solid color-mix(in srgb, var(--color-text) 10%, transparent);border-radius:1rem;padding:1rem">
    <h2 style="font-size:1rem;margin:0 0 .75rem">Categories</h2>
    <ul style="list-style:none;padding:0;margin:0 0 1.25rem">
        @forelse ($categories as $category)
            <li style="margin-bottom:.35rem"><a href="{{ url('/category/'.$category->slug) }}">{{ $category->name }}</a></li>
        @empty
            <li class="text-muted">No categories</li>
        @endforelse
    </ul>

    <h2 style="font-size:1rem;margin:0 0 .75rem">Recent Posts</h2>
    <ul style="list-style:none;padding:0;margin:0">
        @forelse ($recent as $post)
            <li style="margin-bottom:.5rem">
                <a href="{{ app(\App\Services\PermalinkService::class)->postUrl($post) }}">{{ $post->title }}</a>
            </li>
        @empty
            <li class="text-muted">No posts yet</li>
        @endforelse
    </ul>
</div>
