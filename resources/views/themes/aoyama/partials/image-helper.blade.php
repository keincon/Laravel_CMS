@php
    /**
     * Prefer downloaded PNG assets; fall back to local SVG placeholders.
     */
    if (! function_exists('aoyama_theme_image')) {
        function aoyama_theme_image(string $basename): string
        {
            $basename = ltrim($basename, '/');
            $dir = resource_path('views/themes/aoyama/assets/images');
            $png = $dir.DIRECTORY_SEPARATOR.$basename;
            if (is_file($png)) {
                return '/themes/aoyama/assets/images/'.$basename;
            }
            $svgName = preg_replace('/\.png$/i', '.svg', $basename) ?: $basename;
            $svg = $dir.DIRECTORY_SEPARATOR.$svgName;
            if (is_file($svg)) {
                return '/themes/aoyama/assets/images/'.$svgName;
            }

            return '/themes/aoyama/assets/images/'.$basename;
        }
    }
@endphp
