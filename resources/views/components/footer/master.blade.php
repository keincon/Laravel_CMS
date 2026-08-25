@props(['structure'])

@php
    $style = $structure['style'] ?? [];
    $bg = $style['background'] ?? 'var(--color-surface)';
    $color = $style['text_color'] ?? 'var(--color-text)';
    $site = app(\App\Services\LayoutResolverService::class)->siteName();
@endphp

<footer class="site-footer" style="background: {{ $bg }}; color: {{ $color }};">
    <div class="site-container">
        <div class="footer-grid">
            @foreach (($structure['columns'] ?? []) as $column)
                <div>
                    @foreach (($column['components'] ?? []) as $component)
                        @continue(isset($component['enabled']) && ! $component['enabled'])
                        <div class="mb-3">
                            <x-footer.component :component="$component" :site-name="$site" />
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        @if (!empty($structure['bottom']))
            <div class="footer-bottom">
                @foreach ($structure['bottom'] as $component)
                    @continue(isset($component['enabled']) && ! $component['enabled'])
                    <x-footer.component :component="$component" :site-name="$site" />
                @endforeach
            </div>
        @endif
    </div>
</footer>
