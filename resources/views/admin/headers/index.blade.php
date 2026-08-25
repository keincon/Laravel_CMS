@extends('layouts.admin')
@section('title', 'Headers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Headers</h1>
    <form method="POST" action="{{ route('admin.headers.store') }}" class="d-flex gap-2">
        @csrf
        <input type="text" name="name" class="form-control" placeholder="New header name" required>
        <button class="btn btn-primary" type="submit">Create</button>
    </form>
</div>
<table class="table bg-white">
    <thead><tr><th>Name</th><th>Status</th><th>Default</th><th></th></tr></thead>
    <tbody>
    @foreach ($headers as $header)
        <tr>
            <td>{{ $header->name }}</td>
            <td>{{ $header->status }}</td>
            <td>{{ $header->is_default ? 'Yes' : '—' }}</td>
            <td class="text-end"><a href="{{ route('admin.headers.edit', $header) }}">Edit builder</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
