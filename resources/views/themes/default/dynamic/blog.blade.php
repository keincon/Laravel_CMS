<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="blog"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <span class="badge-type">Dynamic · Blog Archive</span>
    <h1 style="margin-top:.5rem">{{ $dynamicConfig->title ?? 'Blog' }}</h1>
    @if (! empty($dynamicConfig?->description))
        <p style="opacity:.8;margin-bottom:1.5rem">{{ $dynamicConfig->description }}</p>
    @endif

    <x-post-grid :posts="$posts" :layout="$layoutStyle ?? 'list'" />
    <x-pagination :paginator="$posts" />
</x-layout.master>
