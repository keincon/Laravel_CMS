@props([
    'context' => '',
    'class' => 'mb-3',
])

@php
    $block = $context !== '' ? __('admin.help_ux.'.$context) : null;
    $steps = is_array($block) ? ($block['steps'] ?? []) : [];
@endphp

@if (is_array($block) && $steps !== [])
    <aside {{ $attributes->class(['admin-help-next panel', $class]) }} style="border-left:4px solid var(--admin-primary, #2563eb)">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
            <h2 class="h6 mb-0">{{ $block['title'] ?? __('admin.help_ux.title') }}</h2>
            @if (\Illuminate\Support\Facades\Route::has('admin.help.setup-guide'))
                <a class="small" href="{{ route('admin.help.setup-guide') }}">{{ __('admin.help_ux.full_guide') }}</a>
            @endif
        </div>
        @if (! empty($block['intro']))
            <p class="small text-muted mb-2">{{ $block['intro'] }}</p>
        @endif
        <ol class="mb-0 ps-3 small">
            @foreach ($steps as $step)
                @php
                    $text = is_array($step) ? (string) ($step['text'] ?? '') : (string) $step;
                    $linkLabel = is_array($step) ? (string) ($step['link'] ?? '') : '';
                    $routeName = is_array($step) ? (string) ($step['route'] ?? '') : '';
                    $params = is_array($step) && is_array($step['params'] ?? null) ? $step['params'] : [];
                    $url = null;
                    if ($routeName !== '' && \Illuminate\Support\Facades\Route::has($routeName)) {
                        try {
                            $url = route($routeName, $params);
                        } catch (\Throwable) {
                            $url = null;
                        }
                    }
                @endphp
                <li class="mb-1">
                    <span>{{ $text }}</span>
                    @if ($url && $linkLabel !== '')
                        — <a href="{{ $url }}">{{ $linkLabel }}</a>
                    @elseif ($url)
                        — <a href="{{ $url }}">{{ __('admin.help_ux.go') }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </aside>
@endif
