@php
    $theme = app(\App\Services\ThemeService::class);
@endphp

<style id="cms-theme-vars">
{!! $theme->cssBlock() !!}
</style>
<script>
(function () {
    const mode = @json($theme->mode());
    const root = document.documentElement;
    function apply(theme) {
        root.setAttribute('data-theme', theme);
        root.style.colorScheme = theme;
    }
    if (mode === 'system') {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        apply(mq.matches ? 'dark' : 'light');
        mq.addEventListener?.('change', (e) => apply(e.matches ? 'dark' : 'light'));
    } else {
        apply(mode === 'dark' ? 'dark' : 'light');
    }
})();
</script>
