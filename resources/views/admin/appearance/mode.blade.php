@extends('layouts.admin')

@section('title', 'Color Mode')

@section('content')
<h1 class="h3 mb-2">Color Mode</h1>
<p class="text-muted mb-4">Choose light, dark, or follow the visitor's system preference.</p>

<form method="POST" action="{{ route('admin.appearance.mode.update') }}">
    @csrf
    @method('PUT')
    @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
        <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="color_mode" id="mode-{{ $value }}" value="{{ $value }}" @checked($mode === $value)>
            <label class="form-check-label" for="mode-{{ $value }}">{{ $label }}</label>
        </div>
    @endforeach
    <button class="btn btn-primary" type="submit">Save Color Mode</button>
</form>
@endsection
