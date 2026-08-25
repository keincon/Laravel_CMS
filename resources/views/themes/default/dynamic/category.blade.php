<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="category"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <span class="badge-type">Dynamic · Category</span>
    <h1 style="margin-top:.5rem">{{ $category->name }}</h1>
    @if ($category->description)
        <p style="opacity:.8;margin-bottom:1.5rem">{{ $category->description }}</p>
    @endif
    <x-post-grid :posts="$posts" :layout="$layoutStyle ?? 'list'" />
    <x-pagination :paginator="$posts" />
</x-layout.master>
