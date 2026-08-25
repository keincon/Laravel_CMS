<x-layout.master :page="$page ?? null" context="blog">
    <h1>{{ $page?->title ?? 'Blog' }}</h1>
    @forelse ($posts as $post)
        <article class="mb-4 pb-3" style="border-bottom:1px solid color-mix(in srgb, var(--color-text) 12%, transparent)">
            <h2 style="font-size:1.25rem;margin-bottom:.35rem">
                <a href="{{ app(\App\Services\PermalinkService::class)->postUrl($post) }}">{{ $post->title }}</a>
            </h2>
            <p style="opacity:.7;font-size:.9rem;margin:.25rem 0">{{ optional($post->published_at)->toFormattedDateString() }} · {{ $post->author?->name }}</p>
            <p>{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 160) }}</p>
        </article>
    @empty
        <p style="opacity:.7">No posts yet.</p>
    @endforelse
    {{ $posts->withQueryString()->links() }}
</x-layout.master>
