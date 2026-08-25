@php
    $ui = app(\App\Services\UIFrameworkService::class);
@endphp

<x-setup.layout :cms-name="$cmsName" :current-step="$currentStep" title="Appearance">
    <h2 class="{{ $ui->class('heading') }}">Choose UI Framework</h2>
    <p class="{{ $ui->class('subheading') }}">You can switch this later in CMS settings.</p>

    <form method="POST" action="{{ route('setup.appearance.store') }}" x-data="{ selected: '{{ old('ui_framework', $selected) }}' }">
        @csrf
        <input type="hidden" name="ui_framework" :value="selected">

        <div class="row g-3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
            @foreach ($frameworks as $key => $framework)
                <button
                    type="button"
                    @click="selected = '{{ $key }}'"
                    :class="selected === '{{ $key }}' ? '{{ $ui->class('framework_card_selected') }}' : '{{ $ui->class('framework_card') }}'"
                    style="background:transparent;text-align:center"
                >
                    <div style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem">{{ $framework['label'] }}</div>
                    <p class="{{ $ui->class('text_muted') }} mb-3">{{ $framework['description'] }}</p>
                    <span
                        class="{{ $ui->class('btn_secondary') }}"
                        x-text="selected === '{{ $key }}' ? 'Selected' : 'Select'"
                    ></span>
                </button>
            @endforeach
        </div>

        <div class="actions between">
            <x-ui.button href="{{ route('setup.administrator') }}" variant="secondary" type="button">← Back</x-ui.button>
            <x-ui.button type="submit" variant="primary">Continue</x-ui.button>
        </div>
    </form>
</x-setup.layout>
