<x-layout.master
    :page="$page"
    context="page"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
>
    <x-hero :title="$page->title" :subtitle="$page->excerpt">
        <div>{!! $page->content !!}</div>
    </x-hero>
</x-layout.master>
