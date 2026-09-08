@extends('layouts.admin')

@section('title', __('admin.nav.permalinks'))

@section('content')
<h1 class="h3 mb-2">{{ __('admin.nav.permalinks') }}</h1>
<p class="text-muted mb-4">Choose how post URLs are generated. Page and taxonomy URLs stay conflict-free.</p>

<form method="POST" action="{{ route('admin.settings.permalinks.update') }}">
    @csrf
    @method('PUT')
    @foreach ($options as $value => $label)
        <div class="form-check mb-3 p-3 border rounded">
            <input class="form-check-input" type="radio" name="permalink_structure" id="p-{{ $value }}" value="{{ $value }}" @checked($structure === $value)>
            <label class="form-check-label" for="p-{{ $value }}">
                <strong>{{ $label }}</strong>
                <div class="small text-muted">Example: {{ url($label === '/{slug}' ? '/my-first-post' : str_replace('{slug}', 'my-first-post', $label)) }}</div>
            </label>
        </div>
    @endforeach
    <button class="btn btn-primary" type="submit">Save Permalinks</button>
</form>
@endsection
