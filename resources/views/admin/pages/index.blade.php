@extends('layouts.admin')
@section('title', 'Pages')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3">Pages</h1>
    <a class="btn btn-primary" href="{{ route('admin.pages.create') }}">Add Page</a>
</div>
<table class="table bg-white">
    <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Template</th><th></th></tr></thead>
    <tbody>
    @foreach ($pages as $page)
        <tr>
            <td>{{ $page->title }}</td>
            <td>/{{ $page->slug }}</td>
            <td>{{ $page->status }}</td>
            <td>{{ $page->template }}</td>
            <td class="text-end"><a href="{{ route('admin.pages.edit', $page) }}">Edit</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $pages->links() }}
@endsection
