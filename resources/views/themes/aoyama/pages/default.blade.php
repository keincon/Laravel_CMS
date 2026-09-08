<x-layout.aoyama
    :page="$page ?? null"
    context="{{ $context ?? 'page' }}"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <article>
        <header class="ao-page-header">
            <h1>{{ $page->title ?? 'ページ' }}</h1>
            @if (! empty($page?->excerpt))
                <p class="ao-excerpt">{{ $page->excerpt }}</p>
            @endif
        </header>

        <div class="ao-content-html">
            {!! $page->content ?? '' !!}
            @if (! empty($page?->custom_html))
                <div class="cms-custom-html" style="margin-top:1.5rem">{!! $page->custom_html !!}</div>
            @endif
        </div>
    </article>
</x-layout.aoyama>
