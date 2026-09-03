<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="blog"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <div class="theme-screen theme-aurora-blog">
        <header class="theme-hero" style="margin-bottom:1.5rem">
            <span class="badge-type">Aurora · Blog</span>
            <h1 style="margin-top:.5rem">{{ $dynamicConfig->title ?? 'Blog' }}</h1>
            @if (! empty($dynamicConfig?->description))
                <p style="opacity:.85">{{ $dynamicConfig->description }}</p>
            @endif
        </header>
        <x-post-grid :posts="$posts" :layout="$layoutStyle ?? 'list'" />
        <x-pagination :paginator="$posts" />
    </div>
</x-layout.master>
