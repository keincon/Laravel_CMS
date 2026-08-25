<x-layout.master context="blog">
    <h1>Author: {{ $author->name }}</h1>
    @foreach ($posts as $post)
        <p><a href="{{ app(\App\Services\PermalinkService::class)->postUrl($post) }}">{{ $post->title }}</a></p>
    @endforeach
    {{ $posts->links() }}
</x-layout.master>
