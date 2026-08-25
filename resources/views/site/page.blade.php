<x-layout.master :page="$page" context="page">
    <article>
        <h1>{{ $page->title }}</h1>
        <div>{!! $page->content !!}</div>
    </article>
</x-layout.master>
