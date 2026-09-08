@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $timezoneOptions = collect($timezones)->mapWithKeys(fn ($tz) => [$tz => $tz])->all();
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" :title="__('setup.website.title')">
    <h2 class="{{ $ui->class('heading') }}">{{ __('setup.website.heading') }}</h2>
    <p class="{{ $ui->class('subheading') }}">{{ __('setup.website.subheading') }}</p>

    <form method="POST" action="{{ route('setup.website.store') }}">
        @csrf

        <x-ui.input name="name" :label="__('setup.website.name')" :value="$website['name']" required />
        <x-ui.input name="description" :label="__('setup.website.description')" :value="$website['description']" />
        <x-ui.input name="url" :label="__('setup.website.url')" type="url" :value="$website['url']" required />
        <x-ui.select name="timezone" :label="__('setup.website.timezone')" :value="$website['timezone']" :options="$timezoneOptions" required />
        <x-ui.select name="language" :label="__('setup.website.language')" :value="$website['language']" :options="$languages" required />
        <x-ui.select name="date_format" :label="__('setup.website.date_format')" :value="$website['date_format']" :options="$dateFormats" required />

        <div class="actions between">
            <x-ui.button href="{{ route('setup.database') }}" variant="secondary" type="button">{{ __('setup.website.back') }}</x-ui.button>
            <x-ui.button type="submit" variant="primary">{{ __('setup.website.continue') }}</x-ui.button>
        </div>
    </form>
</x-setup.layout>
