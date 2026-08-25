<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="404"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <span class="badge-type">Dynamic · 404</span>
    <section style="text-align:center;padding:3rem 1rem">
        <h1 style="font-size:2.5rem;margin-bottom:.5rem">{{ $dynamicConfig->title ?? 'Page Not Found' }}</h1>
        <p style="opacity:.8;margin-bottom:1.5rem">{{ $dynamicConfig->message ?? "Sorry, the page you're looking for doesn't exist." }}</p>
        @if (! empty($dynamicConfig?->image_url))
            <p><img src="{{ $dynamicConfig->image_url }}" alt="" style="max-width:280px;margin:0 auto 1.5rem;display:block"></p>
        @endif
        <a class="cta-btn" href="{{ url($dynamicConfig->button_url ?: '/') }}">
            {{ $dynamicConfig->button_label ?: 'Go Home' }}
        </a>
    </section>
</x-layout.master>
