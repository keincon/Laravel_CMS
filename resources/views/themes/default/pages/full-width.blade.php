<x-layout.master
    :page="$page"
    context="page"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <article>
        @if ($page->featuredImage ?? null)
    <img src="{{ $page->featuredImage->url() }}" alt="{{ $page->featuredImage->alt ?: $page->title }}"
         style="width:100%;max-height:420px;object-fit:cover;border-radius:.75rem;margin-bottom:1.25rem">
@endif
<h1>{{ $page->title }}</h1>
        <div>{!! $page->content !!}
        @if (!empty($page->custom_html))
            <div class="cms-custom-html" style="margin-top:1.5rem">{!! $page->custom_html !!}</div>
        @endif</div>
    </article>
</x-layout.master>
