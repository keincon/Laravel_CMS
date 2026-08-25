@extends('layouts.admin')
@section('title', 'Footers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Footers</h1>
    <form method="POST" action="{{ route('admin.footers.store') }}" class="d-flex gap-2">
        @csrf
        <input type="text" name="name" class="form-control" placeholder="New footer name" required>
        <button class="btn btn-primary" type="submit">Create</button>
    </form>
</div>
<table class="table bg-white">
    <thead><tr><th>Name</th><th>Status</th><th>Default</th><th></th></tr></thead>
    <tbody>
    @foreach ($footers as $footer)
        <tr>
            <td>{{ $footer->name }}</td>
            <td>{{ $footer->status }}</td>
            <td>{{ $footer->is_default ? 'Yes' : '—' }}</td>
            <td class="text-end"><a href="{{ route('admin.footers.edit', $footer) }}">Edit builder</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
