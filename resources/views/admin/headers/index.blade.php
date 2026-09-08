@extends('layouts.admin')
@section('title', __('admin.nav.header'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">{{ __('admin.nav.header') }}</h1>
    <form method="POST" action="{{ route('admin.headers.store') }}" class="d-flex gap-2">
        @csrf
        <input type="text" name="name" class="form-control" placeholder="{{ __('admin.headers.new_name') }}" required>
        <button class="btn btn-primary" type="submit">{{ __('admin.ui.create') }}</button>
    </form>
</div>
<table class="table bg-white">
    <thead>
        <tr>
            <th>{{ __('admin.ui.name') }}</th>
            <th>{{ __('admin.ui.status') }}</th>
            <th>{{ __('admin.ui.default') }}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    @foreach ($headers as $header)
        <tr>
            <td>{{ $header->name }}</td>
            <td>{{ __('admin.ui.statuses.'.$header->status) }}</td>
            <td>{{ $header->is_default ? __('admin.ui.yes') : '—' }}</td>
            <td class="text-end"><a href="{{ route('admin.headers.edit', $header) }}">{{ __('admin.headers.edit_builder') }}</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
