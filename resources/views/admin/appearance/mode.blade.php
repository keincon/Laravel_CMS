@extends('layouts.admin')

@section('title', 'Color Mode')

@section('content')
<p class="page-intro mb-4">
    Site default is <strong>Light</strong>. Visitors can still switch with the theme toggle (saved in their browser). Choose System only if you want to follow each visitor’s device setting.
</p>

<form method="POST" action="{{ route('admin.appearance.mode.update') }}" class="settings-form" x-data="{ mode: @js($mode) }">
    @csrf
    @method('PUT')

    <div class="mode-grid mb-4">
        @foreach ([
            'light' => ['label' => 'Light', 'desc' => 'Bright backgrounds and dark text.'],
            'dark' => ['label' => 'Dark', 'desc' => 'Dim backgrounds and light text.'],
            'system' => ['label' => 'System', 'desc' => 'Follow the visitor device setting.'],
        ] as $value => $meta)
            <label class="mode-card" :class="{ 'is-selected': mode === '{{ $value }}' }">
                <input type="radio" name="color_mode" value="{{ $value }}" class="d-none" x-model="mode" @checked($mode === $value) required>
                <div class="mode-card-inner">
                    <div class="mode-preview mode-preview-{{ $value }}" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="mode-title">{{ $meta['label'] }}</div>
                    <div class="mode-desc">{{ $meta['desc'] }}</div>
                </div>
            </label>
        @endforeach
    </div>

    <div class="panel mb-4">
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <div class="fw-semibold">Preview on this device</div>
                <p class="page-intro mb-0">Try light/dark instantly without saving the site default.</p>
            </div>
            <x-theme-toggle />
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save color mode</button>
    </div>
</form>
@endsection
