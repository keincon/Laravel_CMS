@php
    $ui = app(\App\Services\UIFrameworkService::class);
    $timezoneOptions = collect($timezones)->mapWithKeys(fn ($tz) => [$tz => $tz])->all();
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Website">
    <h2 class="{{ $ui->class('heading') }}">Website Configuration</h2>
    <p class="{{ $ui->class('subheading') }}">Tell us about your website.</p>

    <form method="POST" action="{{ route('setup.website.store') }}">
        @csrf

        <x-ui.input name="name" label="Website Name" :value="$website['name']" required />
        <x-ui.input name="description" label="Website Description" :value="$website['description']" />
        <x-ui.input name="url" label="Website URL" type="url" :value="$website['url']" required />
        <x-ui.select name="timezone" label="Timezone" :value="$website['timezone']" :options="$timezoneOptions" required />
        <x-ui.select name="language" label="Language" :value="$website['language']" :options="$languages" required />
        <x-ui.select name="date_format" label="Date Format" :value="$website['date_format']" :options="$dateFormats" required />

        <div class="actions between">
            <x-ui.button href="{{ route('setup.database') }}" variant="secondary" type="button">← Back</x-ui.button>
            <x-ui.button type="submit" variant="primary">Continue</x-ui.button>
        </div>
    </form>
</x-setup.layout>
