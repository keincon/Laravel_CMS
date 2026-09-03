<x-layout.master :page="$page" context="page">
    <article>
        <h1>{{ $page->title }}</h1>
        <div>{!! $page->content !!}
        @if (!empty($page->custom_html))
            <div class="cms-custom-html" style="margin-top:1.5rem">{!! $page->custom_html !!}</div>
        @endif</div>
    </article>
</x-layout.master>
