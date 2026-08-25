@props(['label' => 'Get started', 'url' => '/'])

<section style="margin:2rem 0;padding:1.5rem;background:var(--color-surface);border-radius:.75rem">
    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between">
        <div>{{ $slot }}</div>
        <a class="cta-btn" href="{{ url($url) }}">{{ $label }}</a>
    </div>
</section>
