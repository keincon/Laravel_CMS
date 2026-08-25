<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="search"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <span class="badge-type">Dynamic · Search</span>
    <h1 style="margin-top:.5rem">{{ $dynamicConfig->title ?? 'Search' }}</h1>

    <div style="margin:1rem 0 1.5rem">
        <x-search-form :query="$query ?? ''" />
    </div>

    @if (($query ?? '') === '')
        <p style="opacity:.7">Enter a search term to find posts, pages, categories, and tags.</p>
    @else
        <p style="margin-bottom:1.5rem">{{ $total }} result{{ $total === 1 ? '' : 's' }} for “{{ $query }}”</p>

        @if ($results['posts']->isNotEmpty())
            <h2 style="font-size:1.1rem">Posts</h2>
            <x-post-grid :posts="$results['posts']" layout="list" />
        @endif

        @if ($results['pages']->isNotEmpty())
            <h2 style="font-size:1.1rem;margin-top:1.5rem">Pages</h2>
            @foreach ($results['pages'] as $pageResult)
                <article class="post-card">
                    <h2><a href="{{ url('/'.$pageResult->slug) }}">{{ $pageResult->title }}</a></h2>
                    <p>{{ $pageResult->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $pageResult->content), 140) }}</p>
                </article>
            @endforeach
        @endif

        @if ($results['categories']->isNotEmpty())
            <h2 style="font-size:1.1rem;margin-top:1.5rem">Categories</h2>
            @foreach ($results['categories'] as $category)
                <x-category-card :category="$category" />
            @endforeach
        @endif

        @if ($results['tags']->isNotEmpty())
            <h2 style="font-size:1.1rem;margin-top:1.5rem">Tags</h2>
            <ul style="list-style:none;padding:0;display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($results['tags'] as $tag)
                    <li><a class="badge-type" href="{{ url('/tag/'.$tag->slug) }}">#{{ $tag->name }}</a></li>
                @endforeach
            </ul>
        @endif

        @if ($total === 0)
            <p style="opacity:.7">No results found. Try a different query.</p>
        @endif
    @endif
</x-layout.master>
