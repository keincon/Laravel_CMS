<x-layout.aoyama
    :dynamic-config="$dynamicConfig ?? null"
    context="404"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <section class="ao-404">
        <h1>{{ $dynamicConfig->title ?? 'ページが見つかりません' }}</h1>
        <p class="ao-excerpt" style="margin-bottom:1.5rem">
            {{ $dynamicConfig->message ?? 'お探しのページは存在しないか、移動した可能性があります。' }}
        </p>
        @if (! empty($dynamicConfig?->image_url))
            <p>
                <img src="{{ $dynamicConfig->image_url }}" alt="" style="max-width:280px;margin:0 auto 1.5rem;display:block">
            </p>
        @endif
        <a class="ao-btn" href="{{ url($dynamicConfig->button_url ?? '/') }}">
            {{ $dynamicConfig->button_label ?? 'トップページへ' }}
        </a>
    </section>
</x-layout.aoyama>
