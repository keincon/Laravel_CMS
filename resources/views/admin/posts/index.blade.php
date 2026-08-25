@extends('layouts.admin')
@section('title', 'Posts')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3">Posts</h1>
    <a class="btn btn-primary" href="{{ route('admin.posts.create') }}">Add Post</a>
</div>
<table class="table bg-white">
    <thead><tr><th>Title</th><th>Author</th><th>Status</th><th>Published</th><th></th></tr></thead>
    <tbody>
    @foreach ($posts as $post)
        <tr>
            <td>{{ $post->title }}</td>
            <td>{{ $post->author?->name }}</td>
            <td>{{ $post->status }}</td>
            <td>{{ optional($post->published_at)->toFormattedDateString() }}</td>
            <td class="text-end"><a href="{{ route('admin.posts.edit', $post) }}">Edit</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $posts->links() }}
@endsection
