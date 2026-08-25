@props(['component', 'siteName' => 'Website'])

@php
    $type = $component['type'] ?? 'text';
    $settings = $component['settings'] ?? [];
@endphp

@switch($type)
    @case('logo')
        <div style="font-weight:700;font-size:1.1rem">{{ $settings['text'] ?? $siteName }}</div>
        @break

    @case('text')
        <p style="margin:0;opacity:.85">{{ $settings['text'] ?? '' }}</p>
        @break

    @case('menu')
        <div style="font-weight:600;margin-bottom:.5rem">{{ $settings['title'] ?? 'Links' }}</div>
        <x-header.navigation :menu-slug="$settings['menu'] ?? 'primary'" />
        @break

    @case('social')
        <x-header.social :links="$settings['links'] ?? []" />
        @break

    @case('contact')
        <div style="font-weight:600;margin-bottom:.5rem">{{ $settings['title'] ?? 'Contact' }}</div>
        @if (!empty($settings['email'])) <div>{{ $settings['email'] }}</div> @endif
        @if (!empty($settings['phone'])) <div>{{ $settings['phone'] }}</div> @endif
        @if (!empty($settings['address'])) <div>{{ $settings['address'] }}</div> @endif
        @break

    @case('newsletter')
        <div style="font-weight:600;margin-bottom:.5rem">{{ $settings['title'] ?? 'Newsletter' }}</div>
        <form onsubmit="return false;" style="display:flex;gap:.5rem;flex-wrap:wrap">
            <input type="email" placeholder="Email" style="flex:1;min-width:140px;padding:.45rem .7rem;border-radius:.5rem;border:1px solid color-mix(in srgb, currentColor 25%, transparent);background:transparent;color:inherit">
            <button type="submit" class="cta-btn">Subscribe</button>
        </form>
        @break

    @case('copyright')
        @php
            $text = $settings['text'] ?? '© {year} {site}';
            $text = str_replace(['{year}', '{site}'], [date('Y'), $siteName], $text);
        @endphp
        <div>{{ $text }}</div>
        @break

    @case('html')
        {!! $settings['html'] ?? '' !!}
        @break

    @default
        <div>{{ $settings['text'] ?? '' }}</div>
@endswitch
