<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="tag"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    @if (config(\'app.debug\'))

        <span class="badge-type">Dynamic · Tag</span>

    @endif
    <h1 style="margin-top:.5rem">#{{ $tag->name }}</h1>
    @if ($tag->description)
        <p style="opacity:.8;margin-bottom:1.5rem">{{ $tag->description }}</p>
    @endif
    <x-post-grid :posts="$posts" :layout="$layoutStyle ?? 'list'" />
    <x-pagination :paginator="$posts" />
</x-layout.master>
