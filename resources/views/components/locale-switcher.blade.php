@php
    $locales = config('cms.ui_locales', ['en' => 'English', 'ja' => '日本語']);
    $current = app()->getLocale();
@endphp
<form method="POST" action="{{ route('locale.update') }}" class="locale-switcher" aria-label="{{ __('common.language') }}">
    @csrf
    <label class="locale-switcher-label visually-hidden" for="locale-switcher-select">{{ __('common.language') }}</label>
    <select
        id="locale-switcher-select"
        name="locale"
        class="locale-switcher-select"
        onchange="this.form.submit()"
        title="{{ __('common.language') }}"
    >
        @foreach ($locales as $code => $label)
            <option value="{{ $code }}" @selected($current === $code)>{{ $label }}</option>
        @endforeach
    </select>
</form>
