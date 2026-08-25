@props(['category'])

<article class="post-card">
    <h2><a href="{{ url('/category/'.$category->slug) }}">{{ $category->name }}</a></h2>
    @if ($category->description)
        <p>{{ \Illuminate\Support\Str::limit(strip_tags($category->description), 120) }}</p>
    @endif
</article>
