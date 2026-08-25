@props(['context' => 'page'])

@php
    $categories = \App\Models\Category::query()->orderBy('name')->limit(12)->get();
    $recent = \App\Models\Post::query()->published()->latest('published_at')->limit(5)->get();
@endphp

<aside style="position:sticky;top:1rem">
    <x-search-form />

    <h3 style="margin:1.5rem 0 .75rem;font-size:1rem">Categories</h3>
    <ul style="list-style:none;padding:0;margin:0">
        @forelse ($categories as $category)
            <li style="margin-bottom:.35rem"><a href="{{ url('/category/'.$category->slug) }}">{{ $category->name }}</a></li>
        @empty
            <li style="opacity:.7">No categories</li>
        @endforelse
    </ul>

    <h3 style="margin:1.5rem 0 .75rem;font-size:1rem">Recent Posts</h3>
    <ul style="list-style:none;padding:0;margin:0">
        @forelse ($recent as $post)
            <li style="margin-bottom:.35rem">
                <a href="{{ app(\App\Services\PermalinkService::class)->postUrl($post) }}">{{ $post->title }}</a>
            </li>
        @empty
            <li style="opacity:.7">No posts</li>
        @endforelse
    </ul>
</aside>
