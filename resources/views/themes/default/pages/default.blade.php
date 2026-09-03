<x-layout.master
    :page="$page"
    context="{{ $context ?? 'page' }}"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <article>
        <span class="badge-type">Static Page</span>
        <h1 style="margin-top:.5rem">{{ $page->title }}</h1>
        @if ($page->excerpt)
            <p style="opacity:.8">{{ $page->excerpt }}</p>
        @endif
        <div>{!! $page->content !!}
        @if (!empty($page->custom_html))
            <div class="cms-custom-html" style="margin-top:1.5rem">{!! $page->custom_html !!}</div>
        @endif</div>
    </article>
</x-layout.master>
