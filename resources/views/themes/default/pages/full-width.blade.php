<x-layout.master
    :page="$page"
    context="page"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <article>
        <h1>{{ $page->title }}</h1>
        <div>{!! $page->content !!}</div>
    </article>
</x-layout.master>
