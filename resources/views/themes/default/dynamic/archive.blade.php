<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="archive"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <span class="badge-type">Dynamic · Date Archive</span>
    <h1 style="margin-top:.5rem">{{ $archiveLabel ?? 'Archives' }}</h1>
    <x-post-grid :posts="$posts" :layout="$layoutStyle ?? 'list'" />
    <x-pagination :paginator="$posts" />
</x-layout.master>
