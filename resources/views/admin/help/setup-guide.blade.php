@extends('layouts.admin')

@section('title', __('admin.setup_guide.title'))

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.setup_guide.title') }}</h1>
        <p class="page-intro mb-0">{{ __('admin.setup_guide.intro') }}</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.dashboard') }}">{{ __('admin.setup_guide.back_dashboard') }}</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="alert alert-info mb-4" role="status">
    {{ __('admin.setup_guide.locale_hint') }}
</div>

{{-- 1. Install --}}
<section class="panel mb-4">
    <h2 class="h5 mb-3">{{ __('admin.setup_guide.install_heading') }}</h2>
    <ol class="setup-guide-list mb-0 ps-3">
        @foreach (is_array($steps) ? $steps : [] as $index => $step)
            <li class="mb-4">
                <h3 class="h6 mb-2">{{ $index + 1 }}. {{ $step['title'] ?? '' }}</h3>
                <div class="text-body" style="white-space: pre-line">{!! $step['body'] ?? '' !!}</div>
                @if (! empty($step['code']))
                    <pre class="mt-2 mb-0 p-3 rounded border bg-light" style="overflow:auto"><code>{{ $step['code'] }}</code></pre>
                @endif
                @if (! empty($step['table']['headers']) && ! empty($step['table']['rows']))
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    @foreach ($step['table']['headers'] as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($step['table']['rows'] as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td><code>{{ $cell }}</code></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</section>

{{-- 2. Seeders --}}
<section class="panel mb-4">
    <h2 class="h5 mb-2">{{ __('admin.setup_guide.seeders_heading') }}</h2>
    <p class="page-intro mb-3">{{ __('admin.setup_guide.seeders_intro') }}</p>

    @if ($canSeed)
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h3 class="h6 mb-1">{{ __('admin.settings.demo_data') }}</h3>
                    <p class="small text-muted mb-3">{{ __('admin.settings.demo_data_desc') }}</p>
                    <form method="POST" action="{{ route('admin.settings.demo-data') }}"
                          onsubmit="return confirm(@js(__('admin.settings.demo_confirm')));">
                        @csrf
                        <label class="d-flex align-items-start gap-2 mb-3">
                            <input type="checkbox" name="fresh" value="1" class="mt-1">
                            <span>
                                <strong>{{ __('admin.settings.replace_demo') }}</strong><br>
                                <small class="text-muted">{{ __('admin.settings.replace_demo_hint') }}</small>
                            </span>
                        </label>
                        <button type="submit" class="btn btn-primary">{{ __('admin.settings.install_demo') }}</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h3 class="h6 mb-1">{{ __('admin.settings.aoyama_title') }}</h3>
                    <p class="small text-muted mb-3">{!! __('admin.settings.aoyama_desc') !!}</p>
                    <form method="POST" action="{{ route('admin.settings.aoyama-data') }}"
                          onsubmit="return confirm(@js(__('admin.settings.aoyama_confirm')));">
                        @csrf
                        <label class="d-flex align-items-start gap-2 mb-3">
                            <input type="checkbox" name="fresh" value="1" class="mt-1">
                            <span>
                                <strong>{{ __('admin.settings.replace_aoyama') }}</strong><br>
                                <small class="text-muted">{{ __('admin.settings.replace_aoyama_hint') }}</small>
                            </span>
                        </label>
                        <button type="submit" class="btn btn-primary">{{ __('admin.settings.install_aoyama') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning mb-0">{{ __('admin.setup_guide.seeders_forbidden') }}</div>
    @endif
</section>

{{-- 3. Dynamic pages & data --}}
<section class="panel mb-4">
    <h2 class="h5 mb-2">{{ __('admin.setup_guide.dynamic_heading') }}</h2>
    <p class="page-intro mb-2">{{ __('admin.setup_guide.dynamic_intro') }}</p>
    <div class="alert alert-warning py-2 small mb-3">{{ __('admin.setup_guide.dynamic_note') }}</div>

    <ol class="setup-guide-list mb-0 ps-3">
        @foreach ($dynamicItems as $index => $item)
            <li class="mb-4">
                <h3 class="h6 mb-2">{{ $index + 1 }}. {{ $item['title'] }}</h3>
                <div class="text-body mb-2" style="white-space: pre-line">{{ $item['body'] }}</div>
                @if ($item['url'])
                    <a class="btn btn-sm btn-outline-primary" href="{{ $item['url'] }}">{{ $item['button'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</section>

{{-- 4. Create a new theme --}}
<section class="panel mb-4">
    <h2 class="h5 mb-2">{{ __('admin.setup_guide.theme_heading') }}</h2>
    <p class="page-intro mb-2">{{ __('admin.setup_guide.theme_intro') }}</p>
    <div class="alert alert-info py-2 small mb-3">{{ __('admin.setup_guide.theme_note') }}</div>

    <ol class="setup-guide-list mb-0 ps-3">
        @foreach ($themeItems as $index => $item)
            <li class="mb-4">
                <h3 class="h6 mb-2">{{ $index + 1 }}. {{ $item['title'] }}</h3>
                <div class="text-body mb-2" style="white-space: pre-line">{{ $item['body'] }}</div>
                @if ($item['url'])
                    <a class="btn btn-sm btn-outline-primary" href="{{ $item['url'] }}">{{ $item['button'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</section>

{{-- 5. How-to --}}
<section class="panel mb-4">
    <h2 class="h5 mb-2">{{ __('admin.setup_guide.howto_heading') }}</h2>
    <p class="page-intro mb-3">{{ __('admin.setup_guide.howto_intro') }}</p>
    <div class="row g-3">
        @foreach ($howtoItems as $item)
            <div class="col-md-6 col-xl-4">
                <div class="border rounded p-3 h-100 d-flex flex-column">
                    <h3 class="h6 mb-2">{{ $item['title'] }}</h3>
                    <p class="small text-muted flex-grow-1 mb-3">{{ $item['body'] }}</p>
                    @if ($item['url'])
                        <a class="btn btn-sm btn-outline-primary align-self-start" href="{{ $item['url'] }}">{{ $item['button'] ?: __('admin.setup_guide.open') }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="panel">
    <h2 class="h6 mb-2">{{ __('admin.setup_guide.related_title') }}</h2>
    <ul class="mb-0">
        <li>{{ __('admin.setup_guide.related_markdown') }}: <code>{{ $markdownPath }}</code></li>
        <li><a href="{{ route('admin.settings.general') }}">{{ __('admin.setup_guide.related_general') }}</a></li>
        <li><a href="{{ route('admin.appearance.themes') }}">{{ __('admin.setup_guide.related_themes') }}</a></li>
    </ul>
</section>
@endsection
