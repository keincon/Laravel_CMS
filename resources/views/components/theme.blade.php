@php
    $theme = app(\App\Services\ThemeService::class);
@endphp

<style id="cms-theme-vars">
{!! $theme->cssBlock() !!}
</style>
<script>
(function () {
    const siteMode = @json($theme->mode());
    // Bump key so older "system" prefs (which followed OS dark) do not stick.
    const storageKey = 'cms-color-mode-v2';
    const root = document.documentElement;

    function resolve(mode) {
        if (mode === 'dark' || mode === 'light') return mode;
        // system → follow OS
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function currentPreference() {
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved === 'light' || saved === 'dark' || saved === 'system') return saved;
        } catch (e) {}
        // Site default is light (not system), so first visit is light even on dark OS.
        return siteMode || 'light';
    }

    function apply(mode) {
        const resolved = resolve(mode);
        root.setAttribute('data-theme', resolved);
        root.style.colorScheme = resolved;
        root.dispatchEvent(new CustomEvent('cms-theme-change', { detail: { mode: mode, resolved: resolved } }));
        document.querySelectorAll('[data-theme-toggle]').forEach((el) => {
            el.setAttribute('data-theme-active', resolved);
            el.setAttribute('aria-label', resolved === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            const label = el.querySelector('[data-theme-label]');
            if (label) label.textContent = resolved === 'dark' ? 'Light' : 'Dark';
        });
    }

    function setMode(mode) {
        if (!['light', 'dark', 'system'].includes(mode)) mode = 'light';
        try { localStorage.setItem(storageKey, mode); } catch (e) {}
        apply(mode);
    }

    function toggle() {
        const resolved = resolve(currentPreference());
        setMode(resolved === 'dark' ? 'light' : 'dark');
    }

    window.cmsTheme = {
        siteMode: siteMode,
        getPreference: currentPreference,
        resolve: () => resolve(currentPreference()),
        setMode: setMode,
        toggle: toggle,
        apply: apply,
    };

    apply(currentPreference());

    try {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (currentPreference() === 'system') apply('system');
        });
    } catch (e) {}
})();
</script>
