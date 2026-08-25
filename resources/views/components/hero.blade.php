@props(['title' => null, 'subtitle' => null])

<section style="margin-bottom:2rem">
    @if ($title)
        <h1 style="margin-bottom:.5rem">{{ $title }}</h1>
    @endif
    @if ($subtitle)
        <p style="opacity:.8;max-width:42rem">{{ $subtitle }}</p>
    @endif
    {{ $slot }}
</section>
