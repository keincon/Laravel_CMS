@props(['structure'])

@php
    $style = $structure['style'] ?? [];
    $sticky = !empty($style['sticky']);
    $bg = $style['background'] ?? 'var(--color-surface)';
    $color = $style['text_color'] ?? 'var(--color-text)';
    $height = $style['height'] ?? '72px';
@endphp

<header
    class="site-header {{ $sticky ? 'is-sticky' : '' }}"
    style="background: {{ $bg }}; color: {{ $color }};"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
>
    <div class="site-container">
        @foreach (($structure['rows'] ?? []) as $row)
            @continue(empty($row['enabled']))
            <div class="site-header-row {{ ($row['id'] ?? '') === 'main' ? 'main' : '' }}" @if(($row['id'] ?? '') === 'main') style="min-height: {{ $height }}" @endif>
                @foreach (($row['components'] ?? []) as $component)
                    @continue(empty($component['enabled']))
                    <div class="{{ in_array($component['type'], ['navigation', 'search', 'button', 'login', 'language'], true) ? 'desktop-only' : '' }}">
                        <x-header.component :component="$component" />
                    </div>
                @endforeach

                @if (($row['id'] ?? '') === 'main')
                    <button type="button" class="mobile-toggle" @click="open = !open" :aria-expanded="open.toString()" aria-controls="mobile-nav" aria-label="Toggle menu">☰</button>
                @endif
            </div>
        @endforeach

        <nav id="mobile-nav" x-show="open" x-cloak x-transition class="pb-3" @click.outside="open = false">
            <x-header.navigation />
        </nav>
    </div>
</header>
