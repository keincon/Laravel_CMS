<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="author"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <span class="badge-type">Dynamic · Author</span>
    <section style="margin-top:.5rem;margin-bottom:2rem">
        <h1>{{ $author->name }}</h1>
        <p style="opacity:.7">{{ '@'.$author->username }}</p>
    </section>
    <h2 style="font-size:1.15rem">Posts by {{ $author->name }}</h2>
    <x-post-grid :posts="$posts" :layout="$layoutStyle ?? 'list'" />
    <x-pagination :paginator="$posts" />
</x-layout.master>