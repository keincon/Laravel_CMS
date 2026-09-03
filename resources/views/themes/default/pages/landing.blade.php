<x-layout.master
    :page="$page"
    context="page"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
>
    <x-hero :title="$page->title" :subtitle="$page->excerpt">
        <div>{!! $page->content !!}
        @if (!empty($page->custom_html))
            <div class="cms-custom-html" style="margin-top:1.5rem">{!! $page->custom_html !!}</div>
        @endif</div>
    </x-hero>
</x-layout.master>
